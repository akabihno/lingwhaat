<?php

namespace App\MessageHandler;

use App\Message\ParseWiktionaryArticlesMessage;
use App\Message\ParseWiktionaryLanguagesMessage;
use App\Repository\LanguageParseScheduleRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class ParseWiktionaryLanguagesMessageHandler
{
    public function __construct(
        private readonly LanguageParseScheduleRepository $repository,
        private readonly MessageBusInterface $bus,
    ) {
    }

    public function __invoke(ParseWiktionaryLanguagesMessage $message): void
    {
        foreach ($this->repository->getAll() as $language) {
            $this->bus->dispatch(
                new ParseWiktionaryArticlesMessage($language->getLanguageName(), $message->getLimit())
            );
        }
    }
}
