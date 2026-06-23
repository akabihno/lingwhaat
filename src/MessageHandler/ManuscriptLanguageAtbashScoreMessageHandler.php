<?php

namespace App\MessageHandler;

use App\Message\ManuscriptLanguageAtbashScoreMessage;
use App\Repository\ManuscriptPatternMatchResultRepository;
use App\Service\Logging\ElasticsearchLogger;
use App\Service\ManuscriptLanguageAtbashScoreService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

#[AsMessageHandler]
class ManuscriptLanguageAtbashScoreMessageHandler
{
    private const string LOG_SERVICE = '[ManuscriptLanguageAtbashScoreMessageHandler]';

    public function __construct(
        private readonly ManuscriptPatternMatchResultRepository $resultRepository,
        private readonly ManuscriptLanguageAtbashScoreService $scoreService,
        private readonly ElasticsearchLogger $logger,
    ) {
    }

    public function __invoke(ManuscriptLanguageAtbashScoreMessage $message): void
    {
        $result = $this->resultRepository->find($message->getResultId());

        if ($result === null) {
            throw new UnrecoverableMessageHandlingException(
                sprintf('ManuscriptPatternMatchResultEntity id=%d not found', $message->getResultId())
            );
        }

        $this->logger->info(sprintf('Atbash scoring result id=%d (matchId=%d, sourceId=%d)', $result->getId(), $result->getMatchId(), $result->getSourceId()), [
            'service' => self::LOG_SERVICE,
        ]);

        $scored = $this->scoreService->score($result);

        $this->resultRepository->updateScoreAtbash(
            $result->getId(),
            $scored['language_code'],
            $scored['language_score'],
        );

        $this->logger->info(sprintf('Result id=%d atbash scored: language=%s score=%.2f', $result->getId(), $scored['language_code'] ?? 'none', $scored['language_score']), [
            'service' => self::LOG_SERVICE,
            'language_code' => $scored['language_code'],
            'language_score_atbash' => $scored['language_score'],
        ]);
    }
}
