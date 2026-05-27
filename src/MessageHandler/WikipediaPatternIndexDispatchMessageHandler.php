<?php

namespace App\MessageHandler;

use App\Message\WikipediaPatternIndexDispatchMessage;
use App\Message\WikipediaPatternIndexLanguageMessage;
use App\Repository\WikipediaArticleRepository;
use App\Service\Logging\ElasticsearchLogger;
use App\Service\Search\WikipediaPatternIndexerService;
use App\Service\WikipediaPatternIndexEpochCoordinator;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class WikipediaPatternIndexDispatchMessageHandler
{
    private const string LOG_SERVICE = '[WikipediaPatternIndexDispatchMessageHandler]';

    public function __construct(
        private readonly WikipediaPatternIndexerService $indexerService,
        private readonly WikipediaArticleRepository $articleRepository,
        private readonly WikipediaPatternIndexEpochCoordinator $coordinator,
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

        $languageCodes = $this->articleRepository->getDistinctLanguageCodes();

        if (!$this->coordinator->tryStartEpoch(count($languageCodes))) {
            $this->logger->info('Previous Wikipedia pattern index epoch still in progress — skipping this tick', [
                'service' => self::LOG_SERVICE,
            ]);
            return;
        }

        // Now safe to nuke: lock held, no other epoch can be running.
        $this->indexerService->deleteIndex();
        $this->logger->info('Deleted existing index', ['service' => self::LOG_SERVICE]);

        $this->logger->info(sprintf('Fanning out indexing for %d languages', count($languageCodes)), [
            'service' => self::LOG_SERVICE,
            'languageCodes' => $languageCodes,
        ]);

        foreach ($languageCodes as $languageCode) {
            $this->bus->dispatch(new WikipediaPatternIndexLanguageMessage(
                $languageCode,
                $message->getWindowSize(),
                $message->getArticleLimit(),
            ));
        }

        // ManuscriptPatternMatchSearchMessage is dispatched by the last per-language handler
        // to finish — see WikipediaPatternIndexLanguageMessageHandler.
    }
}
