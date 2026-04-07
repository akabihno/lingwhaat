<?php

namespace App\MessageHandler;

use App\Entity\WikipediaPatternIndexOffsetEntity;
use App\Message\ManuscriptPatternMatchSearchMessage;
use App\Message\WikipediaPatternIndexDispatchMessage;
use App\Repository\WikipediaArticleRepository;
use App\Repository\WikipediaPatternIndexOffsetRepository;
use App\Service\Search\WikipediaPatternIndexerService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class WikipediaPatternIndexDispatchMessageHandler
{
    public function __construct(
        private readonly WikipediaPatternIndexerService $indexerService,
        private readonly WikipediaArticleRepository $articleRepository,
        private readonly WikipediaPatternIndexOffsetRepository $offsetRepository,
        private readonly MessageBusInterface $bus,
    ) {
    }

    public function __invoke(WikipediaPatternIndexDispatchMessage $message): void
    {
        $this->indexerService->deleteIndex();

        $languageCodes = $this->articleRepository->getDistinctLanguageCodes();

        foreach ($languageCodes as $languageCode) {
            $offsetEntity = $this->offsetRepository->findByLanguageCode($languageCode);

            if ($offsetEntity === null) {
                $offsetEntity = (new WikipediaPatternIndexOffsetEntity())
                    ->setLanguageCode($languageCode)
                    ->setCurrentOffset(0)
                    ->setWindowSize($message->getWindowSize());
            }

            // Reset offset if window size changed
            if ($offsetEntity->getWindowSize() !== $message->getWindowSize()) {
                $offsetEntity->setCurrentOffset(0)->setWindowSize($message->getWindowSize());
            }

            $startOffset = $offsetEntity->getCurrentOffset();

            $articlesProcessed = $this->indexerService->indexBatchByLanguageCode(
                $message->getWindowSize(),
                $languageCode,
                $message->getArticleLimit(),
                $startOffset
            );

            // If fewer articles than requested were returned, we hit the end — wrap to 0
            $newOffset = $articlesProcessed < $message->getArticleLimit()
                ? 0
                : $startOffset + $articlesProcessed;

            $offsetEntity->setCurrentOffset($newOffset);
            $this->offsetRepository->save($offsetEntity);
        }

        $this->bus->dispatch(new ManuscriptPatternMatchSearchMessage());
    }
}
