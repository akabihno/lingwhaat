<?php

namespace App\MessageHandler;

use App\Entity\WikipediaPatternIndexOffsetEntity;
use App\Message\ManuscriptPatternMatchSearchMessage;
use App\Message\WikipediaPatternIndexLanguageMessage;
use App\Repository\WikipediaPatternIndexOffsetRepository;
use App\Service\Cache\RedisCacheService;
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
    // Safety net for stuck epochs. Set generously above the worst-case duration of indexing a
    // single language; if a worker dies holding the lock, the next tick this many seconds later
    // will be able to acquire it. Released explicitly on normal completion.
    private const int LOCK_TTL_SECONDS = 600;

    public function __construct(
        private readonly WikipediaPatternIndexerService $indexerService,
        private readonly WikipediaPatternIndexOffsetRepository $offsetRepository,
        private readonly LockFactory $lockFactory,
        private readonly RedisCacheService $cache,
        private readonly MessageBusInterface $bus,
        private readonly ElasticsearchLogger $logger,
    ) {
    }

    public function __invoke(WikipediaPatternIndexLanguageMessage $message): void
    {
        $languageCode = $message->getLanguageCode();
        $windowSize = $message->getWindowSize();
        $articleLimit = $message->getArticleLimit();

        $lock = $this->lockFactory->createLock(
            self::LOCK_RESOURCE_PREFIX . $languageCode,
            self::LOCK_TTL_SECONDS,
            false,
        );

        if (!$lock->acquire()) {
            // Another worker is already indexing this language. Ack and move on; that worker
            // owns the dedup marker and will clear it, so we must not touch it here.
            $this->logger->info(sprintf('Previous indexing for %s still in flight — skipping', $languageCode), [
                'service' => self::LOG_SERVICE,
                'languageCode' => $languageCode,
            ]);
            return;
        }

        try {
            $existing = $this->offsetRepository->findByLanguageCode($languageCode);
            $afterId = ($existing !== null && $existing->getWindowSize() === $windowSize)
                ? $existing->getLastArticleId()
                : 0;

            $this->logger->info(sprintf('Indexing language: %s (afterId: %d)', $languageCode, $afterId), [
                'service' => self::LOG_SERVICE,
            ]);

            // Build into a fresh write index (refresh disabled for bulk speed).
            // promoteToAlias() swaps the alias atomically so search always sees a complete index.
            $concreteIndex = $this->indexerService->prepareWriteIndex($languageCode);
            try {
                $result = $this->indexerService->indexBatchByLanguageCode(
                    $windowSize,
                    $languageCode,
                    $concreteIndex,
                    $articleLimit,
                    $afterId,
                    fn() => $lock->refresh(),
                );

                $this->indexerService->promoteToAlias($languageCode, $concreteIndex);
            } catch (\Throwable $e) {
                $this->indexerService->deleteConcreteIndex($concreteIndex);
                throw $e;
            }

            $articlesProcessed = $result['processed'];

            // Fewer than a full batch means we reached the end of this language's corpus — reset the
            // cursor so the next pass starts over; otherwise resume from the last id we processed.
            $newCursor = $articlesProcessed < $articleLimit
                ? 0
                : $result['lastArticleId'];

            $offsetEntity = $this->offsetRepository->findByLanguageCode($languageCode)
                ?? (new WikipediaPatternIndexOffsetEntity())->setLanguageCode($languageCode);
            $offsetEntity->setLastArticleId($newCursor)->setWindowSize($windowSize);
            $this->offsetRepository->save($offsetEntity);

            $this->logger->info(sprintf('Indexed %d articles for %s, new cursor (last id): %d', $articlesProcessed, $languageCode, $newCursor), [
                'service' => self::LOG_SERVICE,
            ]);

            // Batch finished cleanly — free the dedup marker so the next scheduler tick can
            // dispatch this language again. Non-lock failures fall through to the message
            // being retried; we deliberately leave the marker set so the retry stays deduped
            // (and the marker's TTL re-opens the language if retries are eventually exhausted).
            $this->cache->delete(WikipediaPatternIndexLanguageMessage::pendingMarkerKey($languageCode));

            // Search the freshly-promoted index immediately. Each language handler triggers
            // its own per-language search so results are captured while matching articles are
            // still in the active index batch (the batch is small and rotates quickly).
            $this->bus->dispatch(new ManuscriptPatternMatchSearchMessage($languageCode));
        } catch (LockConflictedException) {
            // Our lock expired mid-batch and another worker took over this language. The
            // partial write index was already cleaned up above; ack without retrying and let
            // the new lock holder finish (and clear the marker).
            $this->logger->info(sprintf('Lock for %s lost mid-batch — another worker took over, skipping', $languageCode), [
                'service' => self::LOG_SERVICE,
                'languageCode' => $languageCode,
            ]);
        } finally {
            try {
                $lock->release();
            } catch (LockReleasingException) {
                // The lock was already taken over by another worker; nothing for us to release.
            }
        }
    }
}
