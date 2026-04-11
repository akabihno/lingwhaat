<?php

namespace App\MessageHandler;

use App\Entity\WikipediaPatternIndexOffsetEntity;
use App\Message\ManuscriptPatternMatchSearchMessage;
use App\Message\WikipediaPatternIndexDispatchMessage;
use App\Repository\WikipediaArticleRepository;
use App\Repository\WikipediaPatternIndexOffsetRepository;
use App\Service\Logging\ElasticsearchLogger;
use App\Service\Search\WikipediaPatternIndexerService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class WikipediaPatternIndexDispatchMessageHandler
{
    private const string LOG_SERVICE = '[WikipediaPatternIndexDispatchMessageHandler]';

    public function __construct(
        private readonly WikipediaPatternIndexerService $indexerService,
        private readonly WikipediaArticleRepository $articleRepository,
        private readonly WikipediaPatternIndexOffsetRepository $offsetRepository,
        private readonly MessageBusInterface $bus,
        private readonly ElasticsearchLogger $logger,
    ) {
    }

    public function __invoke(WikipediaPatternIndexDispatchMessage $message): void
    {
        $this->logger->info('Starting Wikipedia pattern index dispatch', [
            'service' => self::LOG_SERVICE,
            'windowSize' => $message->getWindowSize(),
            'articleLimit' => $message->getArticleLimit(),
        ]);

        $this->indexerService->deleteIndex();
        $this->logger->info('Deleted existing index', ['service' => self::LOG_SERVICE]);

        $languageCodes = $this->articleRepository->getDistinctLanguageCodes();
        $this->logger->info(sprintf('Found %d language codes to index', count($languageCodes)), [
            'service' => self::LOG_SERVICE,
            'languageCodes' => $languageCodes,
        ]);

        foreach ($languageCodes as $languageCode) {
            // Read offset values before indexing — em->clear() inside the indexer will detach any
            // entity loaded here, so we only capture scalar values and re-fetch after indexing.
            $existing = $this->offsetRepository->findByLanguageCode($languageCode);
            $startOffset = ($existing !== null && $existing->getWindowSize() === $message->getWindowSize())
                ? $existing->getCurrentOffset()
                : 0;

            $this->logger->info(sprintf('Indexing language: %s (offset: %d)', $languageCode, $startOffset), [
                'service' => self::LOG_SERVICE,
            ]);

            $articlesProcessed = $this->indexerService->indexBatchByLanguageCode(
                $message->getWindowSize(),
                $languageCode,
                $message->getArticleLimit(),
                $startOffset
            );

            // Fewer articles than requested → end of data, wrap offset to 0
            $newOffset = $articlesProcessed < $message->getArticleLimit()
                ? 0
                : $startOffset + $articlesProcessed;

            // Re-fetch after em->clear() was called internally by the indexer
            $offsetEntity = $this->offsetRepository->findByLanguageCode($languageCode)
                ?? (new WikipediaPatternIndexOffsetEntity())->setLanguageCode($languageCode);
            $offsetEntity->setCurrentOffset($newOffset)->setWindowSize($message->getWindowSize());
            $this->offsetRepository->save($offsetEntity);

            $this->logger->info(sprintf('Indexed %d articles for %s, new offset: %d', $articlesProcessed, $languageCode, $newOffset), [
                'service' => self::LOG_SERVICE,
            ]);
        }

        $this->logger->info('Dispatching ManuscriptPatternMatchSearchMessage', ['service' => self::LOG_SERVICE]);
        $this->bus->dispatch(new ManuscriptPatternMatchSearchMessage());
    }
}
