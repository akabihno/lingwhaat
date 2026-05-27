<?php

namespace App\MessageHandler;

use App\Entity\WikipediaPatternIndexOffsetEntity;
use App\Message\ManuscriptPatternMatchSearchMessage;
use App\Message\WikipediaPatternIndexLanguageMessage;
use App\Repository\WikipediaPatternIndexOffsetRepository;
use App\Service\Logging\ElasticsearchLogger;
use App\Service\Search\WikipediaPatternIndexerService;
use App\Service\WikipediaPatternIndexEpochCoordinator;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class WikipediaPatternIndexLanguageMessageHandler
{
    private const string LOG_SERVICE = '[WikipediaPatternIndexLanguageMessageHandler]';

    public function __construct(
        private readonly WikipediaPatternIndexerService $indexerService,
        private readonly WikipediaPatternIndexOffsetRepository $offsetRepository,
        private readonly WikipediaPatternIndexEpochCoordinator $coordinator,
        private readonly MessageBusInterface $bus,
        private readonly ElasticsearchLogger $logger,
    ) {
    }

    public function __invoke(WikipediaPatternIndexLanguageMessage $message): void
    {
        $languageCode = $message->getLanguageCode();
        $windowSize = $message->getWindowSize();
        $articleLimit = $message->getArticleLimit();

        try {
            $existing = $this->offsetRepository->findByLanguageCode($languageCode);
            $startOffset = ($existing !== null && $existing->getWindowSize() === $windowSize)
                ? $existing->getCurrentOffset()
                : 0;

            $this->logger->info(sprintf('Indexing language: %s (offset: %d)', $languageCode, $startOffset), [
                'service' => self::LOG_SERVICE,
            ]);

            $articlesProcessed = $this->indexerService->indexBatchByLanguageCode(
                $windowSize,
                $languageCode,
                $articleLimit,
                $startOffset,
            );

            // Fewer articles than requested → end of data, wrap offset to 0
            $newOffset = $articlesProcessed < $articleLimit
                ? 0
                : $startOffset + $articlesProcessed;

            // Re-fetch after em->clear() was called internally by the indexer
            $offsetEntity = $this->offsetRepository->findByLanguageCode($languageCode)
                ?? (new WikipediaPatternIndexOffsetEntity())->setLanguageCode($languageCode);
            $offsetEntity->setCurrentOffset($newOffset)->setWindowSize($windowSize);
            $this->offsetRepository->save($offsetEntity);

            $this->logger->info(sprintf('Indexed %d articles for %s, new offset: %d', $articlesProcessed, $languageCode, $newOffset), [
                'service' => self::LOG_SERVICE,
            ]);
        } finally {
            // Record completion regardless of success so a single failing language doesn't strand
            // the epoch forever. The lock TTL is the final safety net.
            $allDone = $this->coordinator->recordLanguageCompletion($languageCode);

            if ($allDone && $this->coordinator->tryMarkSearchDispatched()) {
                $this->logger->info('All languages indexed — dispatching ManuscriptPatternMatchSearchMessage', [
                    'service' => self::LOG_SERVICE,
                    'languageCode' => $languageCode,
                ]);
                $this->bus->dispatch(new ManuscriptPatternMatchSearchMessage());
                $this->coordinator->endEpoch();
            }
        }
    }
}
