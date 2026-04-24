<?php

namespace App\MessageHandler;

use App\Message\ManuscriptAlphabetDecodeDispatchMessage;
use App\Message\ManuscriptAlphabetDecodeMessage;
use App\Repository\ManuscriptPatternMatchRepository;
use App\Service\Logging\ElasticsearchLogger;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class ManuscriptAlphabetDecodeDispatchMessageHandler
{
    private const string LOG_SERVICE = '[ManuscriptAlphabetDecodeDispatchMessageHandler]';

    public function __construct(
        private readonly ManuscriptPatternMatchRepository $matchRepository,
        private readonly MessageBusInterface $bus,
        private readonly ElasticsearchLogger $logger,
    ) {
    }

    public function __invoke(ManuscriptAlphabetDecodeDispatchMessage $message): void
    {
        $matches = $this->matchRepository->getAll();

        $this->logger->info(
            sprintf('Dispatching alphabet decode for %d records, language=%s', count($matches), $message->getLanguageCode()),
            ['service' => self::LOG_SERVICE, 'language_code' => $message->getLanguageCode()]
        );

        foreach ($matches as $match) {
            $this->bus->dispatch(new ManuscriptAlphabetDecodeMessage(
                $match->getId(),
                $message->getLanguageCode(),
                $message->getWindowSize(),
            ));
        }
    }
}
