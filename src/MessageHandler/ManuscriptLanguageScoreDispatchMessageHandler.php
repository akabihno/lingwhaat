<?php

namespace App\MessageHandler;

use App\Message\ManuscriptLanguageScoreDispatchMessage;
use App\Message\ManuscriptLanguageScoreMessage;
use App\Repository\ManuscriptPatternMatchResultRepository;
use App\Service\Logging\ElasticsearchLogger;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class ManuscriptLanguageScoreDispatchMessageHandler
{
    private const string LOG_SERVICE = '[ManuscriptLanguageScoreDispatchMessageHandler]';

    public function __construct(
        private readonly ManuscriptPatternMatchResultRepository $resultRepository,
        private readonly MessageBusInterface $bus,
        private readonly ElasticsearchLogger $logger,
    ) {
    }

    public function __invoke(ManuscriptLanguageScoreDispatchMessage $message): void
    {
        $unscored = $this->resultRepository->findUnscored();

        $unscored
            |> count(...)
            |> (fn($x) => sprintf('Dispatching language score for %d unscored results', $x))
            |> (fn($x) => $this->logger->info($x, ['service' => self::LOG_SERVICE,]));

        foreach ($unscored as $result) {
            $this->bus->dispatch(new ManuscriptLanguageScoreMessage($result->getId()));
        }
    }
}
