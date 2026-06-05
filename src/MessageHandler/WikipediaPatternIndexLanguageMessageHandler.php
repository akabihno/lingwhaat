<?php

namespace App\MessageHandler;

use App\Entity\WikipediaPatternIndexOffsetEntity;
use App\Message\ManuscriptPatternMatchSearchMessage;
use App\Message\WikipediaPatternIndexLanguageMessage;
use App\Repository\WikipediaPatternIndexOffsetRepository;
use App\Service\Logging\ElasticsearchLogger;
use App\Service\Search\WikipediaPatternIndexerService;
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
    private const int LOCK_TTL_SECONDS = 1800;

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
        $articleLimit = $message->getArticleLimit();

        $lock = $this->lockFactory->createLock(
            self::LOCK_RESOURCE_PREFIX . $languageCode,
            self::LOCK_TTL_SECONDS,
            false,
        );

        if (!$lock->acquire()) {
            $this->logger->info(sprintf('Previous indexing for %s still in flight — skipping', $languageCode), [
                'service' => self::LOG_SERVICE,
                'languageCode' => $languageCode,
            ]);
            return;
        }

        try {
            $existing = $this->offsetRepository->findByLanguageCode($languageCode);
            $startOffset = ($existing !== null && $existing->getWindowSize() === $windowSize)
                ? $existing->getCurrentOffset()
                : 0;

            $this->logger->info(sprintf('Indexing language: %s (offset: %d)', $languageCode, $startOffset), [
                'service' => self::LOG_SERVICE,
            ]);

            // Nuke the per-language index before this cycle's run. Original "delete-on-every-cycle"
            // behaviour from the global dispatcher is preserved per-language here.
            $this->indexerService->deleteIndexForLanguage($languageCode);

            $articlesProcessed = $this->indexerService->indexBatchByLanguageCode(
                $windowSize,
                $languageCode,
                $articleLimit,
                $startOffset,
            );

            $newOffset = $articlesProcessed < $articleLimit
                ? 0
                : $startOffset + $articlesProcessed;

            $offsetEntity = $this->offsetRepository->findByLanguageCode($languageCode)
                ?? (new WikipediaPatternIndexOffsetEntity())->setLanguageCode($languageCode);
            $offsetEntity->setCurrentOffset($newOffset)->setWindowSize($windowSize);
            $this->offsetRepository->save($offsetEntity);

            $this->logger->info(sprintf('Indexed %d articles for %s, new offset: %d', $articlesProcessed, $languageCode, $newOffset), [
                'service' => self::LOG_SERVICE,
            ]);

            // Trigger the manuscript search for this language now that its index is fresh.
            // Wrapped in try-catch: the dispatch uses the `async` DoctrineTransport which can
            // share the DBAL connection with this consumer's transport. If the connection is in
            // a bad state (e.g. due to the --keepalive signal firing mid-query), the INSERT
            // fails. Logging and continuing preserves the indexing work; the scheduler's
            // periodic ManuscriptPatternMatchSearchMessage covers the fallback.
            try {
                $this->bus->dispatch(new ManuscriptPatternMatchSearchMessage($languageCode));
            } catch (\Throwable $e) {
                $this->logger->warning(sprintf('Failed to dispatch ManuscriptPatternMatchSearchMessage for %s: %s', $languageCode, $e->getMessage()), [
                    'service' => self::LOG_SERVICE,
                    'languageCode' => $languageCode,
                ]);
            }
        } finally {
            $lock->release();
        }
    }
}
