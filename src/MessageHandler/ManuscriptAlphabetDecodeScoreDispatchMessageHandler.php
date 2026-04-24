<?php

namespace App\MessageHandler;

use App\Message\ManuscriptAlphabetDecodeScoreDispatchMessage;
use App\Message\ManuscriptAlphabetDecodeScoreMessage;
use App\Repository\ManuscriptAlphabetDecodeResultRepository;
use App\Service\Logging\ElasticsearchLogger;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class ManuscriptAlphabetDecodeScoreDispatchMessageHandler
{
    private const string LOG_SERVICE = '[ManuscriptAlphabetDecodeScoreDispatchMessageHandler]';

    public function __construct(
        private readonly ManuscriptAlphabetDecodeResultRepository $resultRepository,
        private readonly MessageBusInterface $bus,
        private readonly ElasticsearchLogger $logger,
    ) {
    }

    public function __invoke(ManuscriptAlphabetDecodeScoreDispatchMessage $message): void
    {
        $unscored = $this->resultRepository->findUnscored();

        $unscored
            |> count(...)
            |> (fn($x) => sprintf('Dispatching language score for %d unscored decode results', $x))
            |> (fn($x) => $this->logger->info($x, ['service' => self::LOG_SERVICE]));

        foreach ($unscored as $result) {
            $this->bus->dispatch(new ManuscriptAlphabetDecodeScoreMessage($result->getId()));
        }
    }
}
