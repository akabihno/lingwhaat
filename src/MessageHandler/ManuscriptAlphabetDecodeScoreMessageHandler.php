<?php

namespace App\MessageHandler;

use App\Message\ManuscriptAlphabetDecodeScoreMessage;
use App\Repository\ManuscriptAlphabetDecodeResultRepository;
use App\Service\Logging\ElasticsearchLogger;
use App\Service\ManuscriptAlphabetDecodeScoreService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

#[AsMessageHandler]
class ManuscriptAlphabetDecodeScoreMessageHandler
{
    private const string LOG_SERVICE = '[ManuscriptAlphabetDecodeScoreMessageHandler]';

    public function __construct(
        private readonly ManuscriptAlphabetDecodeResultRepository $resultRepository,
        private readonly ManuscriptAlphabetDecodeScoreService $scoreService,
        private readonly ElasticsearchLogger $logger,
    ) {
    }

    public function __invoke(ManuscriptAlphabetDecodeScoreMessage $message): void
    {
        $result = $this->resultRepository->find($message->getResultId());

        if ($result === null) {
            throw new UnrecoverableMessageHandlingException(
                sprintf('ManuscriptAlphabetDecodeResultEntity id=%d not found', $message->getResultId())
            );
        }

        $this->logger->info(
            sprintf('Scoring decode result id=%d (matchId=%d, language=%s)', $result->getId(), $result->getMatchId(), $result->getLanguageCode()),
            ['service' => self::LOG_SERVICE]
        );

        $scored = $this->scoreService->score($result);

        $this->resultRepository->updateScore(
            $result->getId(),
            $scored['language_code'],
            $scored['language_score'],
        );

        $this->logger->info(
            sprintf('Decode result id=%d scored: language=%s score=%.2f', $result->getId(), $scored['language_code'], $scored['language_score']),
            ['service' => self::LOG_SERVICE, 'language_code' => $scored['language_code'], 'language_score' => $scored['language_score']]
        );
    }
}
