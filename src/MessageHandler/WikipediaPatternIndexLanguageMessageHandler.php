<?php

namespace App\MessageHandler;

use App\Entity\WikipediaPatternIndexOffsetEntity;
use App\Message\ManuscriptPatternMatchSearchMessage;
use App\Message\WikipediaPatternIndexLanguageMessage;
use App\Repository\WikipediaPatternIndexOffsetRepository;
use App\Service\Logging\ElasticsearchLogger;
use App\Service\Search\WikipediaPatternIndexerService;
use Symfony\Component\Lock\Exception\LockConflictedException;
use Symfony\Component\Lock\Exception\LockReleasingException;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class WikipediaPatternIndexLanguageMessageHandler
{
    private const string LOG_SERVICE = '[WikipediaPatternIndexLanguageMessageHandler]';
    private const string LOCK_RESOURCE_PREFIX = 'wikipedia-pattern-index-language:';
    // Safety net for stuck workers: if a worker dies without releasing the lock, this is the
    // maximum time before the next run can acquire it. Not used for coordination — normal runs
    // release the lock explicitly and self-dispatch the next message immediately.
    private const int LOCK_TTL_SECONDS = 300;
    // Throttle for the per-language manuscript search dispatch. Indexing self-chains a batch
    // every ~30s and the cursor effectively never wraps to 0 (corpora are millions of articles,
    // batches only a handful), so dispatching a search after every batch floods the async queue.
    // This lock is acquired (never released) before each dispatch; its TTL is the minimum gap
    // between per-language searches. The all-language scheduler sweep covers everything anyway.
    private const string SEARCH_DISPATCH_THROTTLE_PREFIX = 'manuscript-pattern-search-dispatch:';
    private const int SEARCH_DISPATCH_THROTTLE_SECONDS = 600;
    // Target wall-clock duration for each batch. The article limit adapts each run so the next
    // batch takes approximately this long, keeping processing continuous without overloading.
    private const int TARGET_BATCH_MS = 30_000;
    // Hard ceiling on articles per batch regardless of timing measurements.
    public const int MAX_ARTICLE_LIMIT = 500;

    public function __construct(
        private readonly WikipediaPatternIndexerService $indexerService,
        private readonly WikipediaPatternIndexOffsetRepository $offsetRepository,
        private readonly LockFactory $lockFactory,
        private readonly MessageBusInterface $bus,
        private readonly ElasticsearchLogger $logger,
    ) {
    }

    public function __invoke(WikipediaPatternIndexLanguageMessage $message): void
    {
        $languageCode = $message->getLanguageCode();
        $windowSize = $message->getWindowSize();
        $articleLimit = min(self::MAX_ARTICLE_LIMIT, $message->getArticleLimit());

        $lock = $this->lockFactory->createLock(
            self::LOCK_RESOURCE_PREFIX . $languageCode,
            self::LOCK_TTL_SECONDS,
            false,
        );

        if (!$lock->acquire()) {
            // Another worker is already indexing this language; this message is a duplicate
            // (e.g. from a recovery dispatch). Ack and move on.
            $this->logger->info(sprintf('Previous indexing for %s still in flight — skipping', $languageCode), [
                'service' => self::LOG_SERVICE,
                'languageCode' => $languageCode,
            ]);
            return;
        }

        try {
            // Stamp last_run_at before the batch so the dispatch watchdog doesn't re-kick this
            // language while we're running. Uses a direct DQL UPDATE so em->clear() inside
            // indexBatchByLanguageCode cannot detach or invalidate this write.
            // Has no effect for languages with no offset row yet — new languages are safely
            // re-kicked by the watchdog if the first run fails.
            $this->offsetRepository->touchLastRunAt($languageCode);

            $existing = $this->offsetRepository->findByLanguageCode($languageCode);

            $afterId = ($existing !== null && $existing->getWindowSize() === $windowSize)
                ? $existing->getLastArticleId()
                : 0;

            $generation = $existing?->getGeneration() ?? 1;

            $this->logger->info(sprintf('Indexing language: %s (afterId: %d, limit: %d)', $languageCode, $afterId, $articleLimit), [
                'service' => self::LOG_SERVICE,
            ]);

            // Resolve the language's stable index once and write this batch straight into it.
            // No per-batch index create / alias swap / delete: those cluster-state operations are
            // what saturated the single ES node. A failed batch simply propagates and is retried;
            // deterministic doc ids make the re-run an idempotent overwrite, so there is nothing
            // to clean up on the error path.
            $writeIndex = $this->indexerService->ensureWriteIndex($languageCode, $windowSize);
            $batchStartMs = (int) round(microtime(true) * 1000);

            $result = $this->indexerService->indexBatchByLanguageCode(
                $windowSize,
                $languageCode,
                $writeIndex,
                $articleLimit,
                $afterId,
                $generation,
                fn() => $lock->refresh(),
            );

            $durationMs = (int) round(microtime(true) * 1000) - $batchStartMs;
            $articlesProcessed = $result['processed'];

            // A short batch means we reached the end of the corpus: the cursor wraps to 0 and the
            // pass is complete. Prune docs not rewritten this pass (shrunk/removed articles) and
            // advance the generation so the next pass prunes what it in turn leaves stale.
            $wrapped = $articlesProcessed < $articleLimit;
            $newCursor = $wrapped ? 0 : $result['lastArticleId'];
            $newGeneration = $generation;
            if ($wrapped) {
                $this->indexerService->pruneStaleGenerations($writeIndex, $generation);
                $newGeneration = $generation + 1;
            }

            $nextArticleLimit = $this->calculateNextLimit($articleLimit, $articlesProcessed, $durationMs);

            // Re-fetch after indexBatchByLanguageCode: em->clear() inside doIndex detaches all
            // previously-loaded entities, so reusing the $existing reference would cause
            // persist() to attempt an INSERT and fail on the unique constraint.
            $offsetEntity = $this->offsetRepository->findByLanguageCode($languageCode)
                ?? (new WikipediaPatternIndexOffsetEntity())->setLanguageCode($languageCode);
            $offsetEntity
                ->setLastArticleId($newCursor)
                ->setWindowSize($windowSize)
                ->setLastRunAt(new \DateTimeImmutable())
                ->setNextArticleLimit($nextArticleLimit)
                ->setGeneration($newGeneration);
            $this->offsetRepository->save($offsetEntity);

            $this->logger->info(
                sprintf('Indexed %d articles for %s in %d ms, cursor: %d, next limit: %d',
                    $articlesProcessed, $languageCode, $durationMs, $newCursor, $nextArticleLimit),
                ['service' => self::LOG_SERVICE],
            );

            // Kick a per-language manuscript search, but at most once per throttle window so the
            // ~30s batch cadence doesn't flood the async queue. The throttle lock is intentionally
            // never released: its TTL is the minimum gap between searches for this language. The
            // search handler's own lock additionally drops any overlapping duplicate.
            $searchThrottle = $this->lockFactory->createLock(
                self::SEARCH_DISPATCH_THROTTLE_PREFIX . $languageCode,
                self::SEARCH_DISPATCH_THROTTLE_SECONDS,
                false,
            );
            if ($searchThrottle->acquire()) {
                $this->bus->dispatch(new ManuscriptPatternMatchSearchMessage($languageCode));
            }

            // Self-chain: immediately queue the next batch so processing continues without
            // waiting for the periodic dispatch handler.
            $this->bus->dispatch(new WikipediaPatternIndexLanguageMessage(
                $languageCode,
                $windowSize,
                $nextArticleLimit,
            ));
        } catch (LockConflictedException) {
            // Lock expired mid-batch (ES flush took longer than LOCK_TTL_SECONDS between
            // heartbeat calls). lastRunAt was already set, so the dispatch watchdog will
            // re-kick this language after MAX_IDLE_SECONDS.
            $this->logger->info(sprintf('Lock for %s expired mid-batch — watchdog will recover after idle timeout', $languageCode), [
                'service' => self::LOG_SERVICE,
                'languageCode' => $languageCode,
            ]);
        } finally {
            try {
                $lock->release();
            } catch (LockReleasingException) {
                // Lock was already expired or taken over; nothing to release.
            }
        }
    }

    private function calculateNextLimit(int $currentLimit, int $articlesProcessed, int $durationMs): int
    {
        if ($articlesProcessed <= 0 || $durationMs <= 0) {
            return $currentLimit;
        }
        $msPerArticle = $durationMs / $articlesProcessed;
        return min(self::MAX_ARTICLE_LIMIT, max(1, (int) ceil(self::TARGET_BATCH_MS / $msPerArticle)));
    }
}
