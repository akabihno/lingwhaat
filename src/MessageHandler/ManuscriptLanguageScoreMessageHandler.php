<?php

namespace App\MessageHandler;

use App\Message\ManuscriptLanguageScoreMessage;
use App\Repository\ManuscriptPatternMatchResultRepository;
use App\Service\Logging\ElasticsearchLogger;
use App\Service\ManuscriptLanguageScoreService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

#[AsMessageHandler]
class ManuscriptLanguageScoreMessageHandler
{
    private const string LOG_SERVICE = '[ManuscriptLanguageScoreMessageHandler]';

    public function __construct(
        private readonly ManuscriptPatternMatchResultRepository $resultRepository,
        private readonly ManuscriptLanguageScoreService $scoreService,
        private readonly ElasticsearchLogger $logger,
    ) {
    }

    public function __invoke(ManuscriptLanguageScoreMessage $message): void
    {
        $result = $this->resultRepository->find($message->getResultId());

        if ($result === null) {
            throw new UnrecoverableMessageHandlingException(
                sprintf('ManuscriptPatternMatchResultEntity id=%d not found', $message->getResultId())
            );
        }

        $this->logger->info(sprintf('Scoring result id=%d (matchId=%d, sourceId=%d)', $result->getId(), $result->getMatchId(), $result->getSourceId()), [
            'service' => self::LOG_SERVICE,
        ]);

        $scored = $this->scoreService->score($result);

        $this->resultRepository->updateScore(
            $result->getId(),
            $scored['language_code'],
            $scored['language_score'],
        );

        $this->logger->info(sprintf('Result id=%d scored: language=%s score=%.2f', $result->getId(), $scored['language_code'] ?? 'none', $scored['language_score']), [
            'service' => self::LOG_SERVICE,
            'language_code' => $scored['language_code'],
            'language_score' => $scored['language_score'],
        ]);
    }
}
