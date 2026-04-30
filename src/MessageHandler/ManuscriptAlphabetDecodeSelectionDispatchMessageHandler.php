<?php

namespace App\MessageHandler;

use App\Message\ManuscriptAlphabetDecodeSelectionDispatchMessage;
use App\Message\ManuscriptAlphabetDecodeSelectionMessage;
use App\Repository\ManuscriptAlphabetDecodeResultRepository;
use App\Service\Logging\ElasticsearchLogger;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class ManuscriptAlphabetDecodeSelectionDispatchMessageHandler
{
    private const string LOG_SERVICE = '[ManuscriptAlphabetDecodeSelectionDispatchMessageHandler]';
    private const int BUDGET_PER_RUN = 50;

    public function __construct(
        private readonly ManuscriptAlphabetDecodeResultRepository $resultRepository,
        private readonly MessageBusInterface $bus,
        private readonly ElasticsearchLogger $logger,
    ) {
    }

    public function __invoke(ManuscriptAlphabetDecodeSelectionDispatchMessage $message): void
    {
        $unprocessed = $this->resultRepository->findUnprocessed(self::BUDGET_PER_RUN);

        $this->logger->info(
            sprintf('Dispatching OpenAI selection for %d decode results (budget=%d)', count($unprocessed), self::BUDGET_PER_RUN),
            ['service' => self::LOG_SERVICE]
        );

        foreach ($unprocessed as $result) {
            $this->bus->dispatch(new ManuscriptAlphabetDecodeSelectionMessage($result->getId()));
        }
    }
}
