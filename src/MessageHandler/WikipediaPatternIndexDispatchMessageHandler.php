<?php

namespace App\MessageHandler;

use App\Message\ManuscriptPatternMatchSearchMessage;
use App\Message\WikipediaPatternIndexDispatchMessage;
use App\Repository\WikipediaArticleRepository;
use App\Service\Search\WikipediaPatternIndexerService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class WikipediaPatternIndexDispatchMessageHandler
{
    public function __construct(
        private readonly WikipediaPatternIndexerService $indexerService,
        private readonly WikipediaArticleRepository $articleRepository,
        private readonly MessageBusInterface $bus,
    ) {
    }

    public function __invoke(WikipediaPatternIndexDispatchMessage $message): void
    {
        $this->indexerService->deleteIndex();

        $languageCodes = $this->articleRepository->getDistinctLanguageCodes();

        foreach ($languageCodes as $languageCode) {
            $this->indexerService->indexBatchByLanguageCode(
                $message->getWindowSize(),
                $languageCode,
                $message->getArticleLimit()
            );
        }

        $this->bus->dispatch(new ManuscriptPatternMatchSearchMessage());
    }
}
