<?php

namespace App\MessageHandler;

use App\Message\ParseWikipediaArticlesMessage;
use App\Message\ParseWikipediaLanguagesMessage;
use App\Repository\WikipediaPatternParseScheduleRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Transport\Receiver\MessageCountAwareInterface;

#[AsMessageHandler]
class ParseWikipediaLanguagesMessageHandler
{
    public function __construct(
        private readonly WikipediaPatternParseScheduleRepository $repository,
        private readonly MessageBusInterface $bus,
        #[Autowire(service: 'messenger.transport.wikipedia_async')]
        private readonly MessageCountAwareInterface $wikipediaTransport,
    ) {
    }

    public function __invoke(ParseWikipediaLanguagesMessage $message): void
    {
        $languages = $this->repository->getAll();

        // This runs every minute and queues one message per language. Skip the tick while a full
        // round is still waiting (workers paused, or slower than the schedule): otherwise the queue
        // grows without bound — it reached 1.4M copies of the same 57 messages while
        // messenger-wikipedia was scaled to 0. Keeping up to one round buffered means running
        // workers never sit idle waiting for the next tick.
        if ($this->wikipediaTransport->getMessageCount() >= count($languages)) {
            return;
        }

        foreach ($languages as $language) {
            $this->bus->dispatch(
                new ParseWikipediaArticlesMessage($language->getLanguageCode())
            );
        }
    }
}
