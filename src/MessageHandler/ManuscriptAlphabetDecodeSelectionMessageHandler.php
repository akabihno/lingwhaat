<?php

namespace App\MessageHandler;

use App\Message\ManuscriptAlphabetDecodeSelectionMessage;
use App\Repository\ManuscriptAlphabetDecodeResultRepository;
use App\Service\Logging\ElasticsearchLogger;
use App\Service\ManuscriptAlphabetDecodeSelectionService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

#[AsMessageHandler]
class ManuscriptAlphabetDecodeSelectionMessageHandler
{
    private const string LOG_SERVICE = '[ManuscriptAlphabetDecodeSelectionMessageHandler]';

    public function __construct(
        private readonly ManuscriptAlphabetDecodeResultRepository $resultRepository,
        private readonly ManuscriptAlphabetDecodeSelectionService $selectionService,
        private readonly ElasticsearchLogger $logger,
    ) {
    }

    public function __invoke(ManuscriptAlphabetDecodeSelectionMessage $message): void
    {
        $result = $this->resultRepository->find($message->getResultId());

        if ($result === null) {
            throw new UnrecoverableMessageHandlingException(
                sprintf('ManuscriptAlphabetDecodeResultEntity id=%d not found', $message->getResultId())
            );
        }

        $this->logger->info(
            sprintf('Selecting decode result id=%d (matchId=%d, language=%s)', $result->getId(), $result->getMatchId(), $result->getLanguageCode()),
            ['service' => self::LOG_SERVICE]
        );

        $selected = $this->selectionService->select($result);

        $this->resultRepository->updateSelection(
            $result->getId(),
            $selected['status'],
            $selected['selected_phrase'],
        );

        $this->logger->info(
            sprintf('Decode result id=%d selection: status=%s phrase=%s', $result->getId(), $selected['status'], $selected['selected_phrase'] ?? '<null>'),
            ['service' => self::LOG_SERVICE, 'status' => $selected['status']]
        );
    }
}
