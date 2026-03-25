<?php

namespace App\MessageHandler;

use App\Message\ParseWiktionaryArticlesMessage;
use App\Message\ParseWiktionaryLanguagesMessage;
use App\Repository\LanguageParseScheduleRepository;
use App\Service\Logging\ElasticsearchLogger;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class ParseWiktionaryLanguagesMessageHandler
{
    public function __construct(
        private readonly LanguageParseScheduleRepository $repository,
        private readonly MessageBusInterface $bus,
        private readonly ElasticsearchLogger $logger,
    ) {
    }

    public function __invoke(ParseWiktionaryLanguagesMessage $message): void
    {
        $languages = $this->repository->getAll();

        $this->logger->info(
            sprintf('Dispatching Wiktionary IPA parsing for %d language(s).', count($languages)),
            ['service' => '[ParseWiktionaryLanguagesMessageHandler]']
        );

        foreach ($languages as $language) {
            $this->bus->dispatch(
                new ParseWiktionaryArticlesMessage($language->getLanguageName(), $message->getLimit())
            );
        }
    }
}
