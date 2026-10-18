<?php

namespace App\MessageHandler;

use App\Entity\WikipediaPatternIndexOffsetEntity;
use App\Message\WikipediaPatternIndexLanguageMessage;
use App\Repository\WikipediaPatternIndexOffsetRepository;
use App\Service\Logging\ElasticsearchLogger;
use App\Service\Metrics\MetricName;
use App\Service\Metrics\PrometheusMetricsService;
use App\Service\Search\ManuscriptCorpusSearchService;
use App\Service\Search\WikipediaCorpusScanner;
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
    // Target wall-clock duration for each batch. The article limit adapts each run so the next
    // batch takes approximately this long, keeping processing continuous without overloading.
    private const int TARGET_BATCH_MS = 30_000;
    // Hard ceiling on articles per batch regardless of timing measurements.
    public const int MAX_ARTICLE_LIMIT = 500;

    public function __construct(
        private readonly WikipediaCorpusScanner $corpusScanner,
        private readonly ManuscriptCorpusSearchService $corpusSearchService,
        private readonly WikipediaPatternIndexOffsetRepository $offsetRepository,
        private readonly LockFactory $lockFactory,
        private readonly MessageBusInterface $bus,
        private readonly ElasticsearchLogger $logger,
        private readonly PrometheusMetricsService $metrics,
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
            // language while we're running.
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

            // Join this batch against the manuscripts in memory: the matcher holds every manuscript
            // window, the scanner streams the batch's corpus windows through it, and the hits are
            // appended as result rows. Nothing about the batch outlives this message, so a failed
            // batch simply propagates and is retried (its cursor was not advanced).
            $matcher = $this->corpusSearchService->buildMatcher($windowSize);

            $batchStartMs = (int) round(microtime(true) * 1000);

            $result = $this->corpusScanner->scan(
                $matcher,
                $languageCode,
                $articleLimit,
                $afterId,
                fn() => $lock->refresh(),
            );

            $articlesProcessed = $result['processed'];

            // incBy is a no-op at 0, so an end-of-corpus empty batch doesn't touch the counter.
            if ($articlesProcessed > 0) {
                $this->metrics
                    ->counter(
                        MetricName::WIKIPEDIA_ARTICLES_INDEXED_TOTAL,
                        'Total Wikipedia articles scanned for manuscript matches by the pattern-index pipeline.',
                        ['language'],
                    )
                    ->incBy($articlesProcessed, [$languageCode]);

                $lock->refresh();
                $this->corpusSearchService->saveResults($matcher, $languageCode);
            }

            // Measure the whole cycle (scan + save) so the adaptive limit keeps total batch
            // wall-clock near the target.
            $durationMs = (int) round(microtime(true) * 1000) - $batchStartMs;

            // A short batch means we reached the end of the corpus: wrap the cursor to 0 to re-sweep
            // the corpus from the start on the next pass.
            $wrapped = $articlesProcessed < $articleLimit;
            $newCursor = $wrapped ? 0 : $result['lastArticleId'];

            $nextArticleLimit = $this->calculateNextLimit($articleLimit, $articlesProcessed, $durationMs);

            // $existing is still managed (nothing in the batch clears the EntityManager any more).
            $offsetEntity = $existing
                ?? (new WikipediaPatternIndexOffsetEntity())->setLanguageCode($languageCode);
            $offsetEntity
                ->setLastArticleId($newCursor)
                ->setWindowSize($windowSize)
                ->setLastRunAt(new \DateTimeImmutable())
                ->setNextArticleLimit($nextArticleLimit)
                ->setGeneration($generation);
            $this->offsetRepository->save($offsetEntity);

            $this->logger->info(
                sprintf('Indexed %d articles for %s in %d ms, cursor: %d, next limit: %d',
                    $articlesProcessed, $languageCode, $durationMs, $newCursor, $nextArticleLimit),
                ['service' => self::LOG_SERVICE],
            );

            // Self-chain: immediately queue the next batch so processing continues without
            // waiting for the periodic dispatch handler.
            $this->bus->dispatch(new WikipediaPatternIndexLanguageMessage(
                $languageCode,
                $windowSize,
                $nextArticleLimit,
            ));
        } catch (LockConflictedException) {
            // Lock expired mid-batch (a chunk took longer than LOCK_TTL_SECONDS between
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
