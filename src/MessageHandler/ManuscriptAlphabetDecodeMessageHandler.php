<?php

namespace App\MessageHandler;

use App\Message\ManuscriptAlphabetDecodeMessage;
use App\Repository\ManuscriptPatternMatchRepository;
use App\Service\Logging\ElasticsearchLogger;
use App\Service\ManuscriptAlphabetDecodeService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

#[AsMessageHandler]
class ManuscriptAlphabetDecodeMessageHandler
{
    private const string LOG_SERVICE = '[ManuscriptAlphabetDecodeMessageHandler]';

    public function __construct(
        private readonly ManuscriptPatternMatchRepository $matchRepository,
        private readonly ManuscriptAlphabetDecodeService $decodeService,
        private readonly ElasticsearchLogger $logger,
    ) {
    }

    public function __invoke(ManuscriptAlphabetDecodeMessage $message): void
    {
        $match = $this->matchRepository->find($message->getMatchId());

        if ($match === null) {
            throw new UnrecoverableMessageHandlingException(
                sprintf('ManuscriptPatternMatchEntity id=%d not found', $message->getMatchId())
            );
        }

        $this->logger->info(
            sprintf('Decoding match id=%d for language=%s', $match->getId(), $message->getLanguageCode()),
            ['service' => self::LOG_SERVICE, 'match_id' => $match->getId(), 'language_code' => $message->getLanguageCode()]
        );

        $saved = $this->decodeService->decode($match, $message->getLanguageCode(), $message->getWindowSize());

        $this->logger->info(
            sprintf('Match id=%d decoded: %d phrases saved for language=%s', $match->getId(), $saved, $message->getLanguageCode()),
            ['service' => self::LOG_SERVICE, 'match_id' => $match->getId(), 'saved' => $saved]
        );
    }
}
