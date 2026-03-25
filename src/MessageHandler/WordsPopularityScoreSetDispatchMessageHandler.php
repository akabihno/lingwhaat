<?php

namespace App\MessageHandler;

use App\Message\WordsPopularityScoreSetDispatchMessage;
use App\Message\WordsPopularityScoreSetMessage;
use App\Repository\WordsPopularityScoreSetScheduleRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class WordsPopularityScoreSetDispatchMessageHandler
{
    private const int ARTICLE_LIMIT = 50;

    public function __construct(
        private readonly WordsPopularityScoreSetScheduleRepository $repository,
        private readonly MessageBusInterface $bus,
    ) {
    }

    public function __invoke(WordsPopularityScoreSetDispatchMessage $message): void
    {
        foreach ($this->repository->getAll() as $entity) {
            $this->bus->dispatch(
                new WordsPopularityScoreSetMessage(
                    $entity->getLanguageCode(),
                    self::ARTICLE_LIMIT,
                    $entity->getOffset()
                )
            );
        }
    }
}
