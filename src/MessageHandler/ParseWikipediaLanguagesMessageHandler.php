<?php

namespace App\MessageHandler;

use App\Message\ParseWikipediaArticlesMessage;
use App\Message\ParseWikipediaLanguagesMessage;
use App\Repository\WikipediaPatternParseScheduleRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class ParseWikipediaLanguagesMessageHandler
{
    public function __construct(
        private readonly WikipediaPatternParseScheduleRepository $repository,
        private readonly MessageBusInterface $bus,
    ) {
    }

    public function __invoke(ParseWikipediaLanguagesMessage $message): void
    {
        foreach ($this->repository->getAll() as $language) {
            $this->bus->dispatch(
                new ParseWikipediaArticlesMessage($language->getLanguageCode())
            );
        }
    }
}
