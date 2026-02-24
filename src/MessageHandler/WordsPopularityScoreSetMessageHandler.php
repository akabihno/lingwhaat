<?php

namespace App\MessageHandler;

use App\Message\WordsPopularityScoreSetMessage;
use App\Service\Search\WordsPopularityScoreSetService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class WordsPopularityScoreSetMessageHandler
{
    public function __construct(
        private readonly WordsPopularityScoreSetService $popularityScoreSetService,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(WordsPopularityScoreSetMessage $message): void
    {
        $languageCode = $message->getLanguageCode();
        $limit = $message->getLimit();
        $offset = $message->getOffset();

        $this->logger->info(
            sprintf('Starting to parse Wikipedia articles for language: %s (limit: %d, offset %d)', $languageCode, $limit, $offset),
            ['handler' => 'WordsPopularityScoreSetMessageHandler']
        );

        try {
            $this->popularityScoreSetService->execute($languageCode, $limit, $offset);

            $this->logger->info(
                sprintf('Successfully completed parsing for language: %s', $languageCode),
                ['handler' => 'WordsPopularityScoreSetMessageHandler']
            );
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf('Failed to parse articles for language: %s. Error: %s', $languageCode, $e->getMessage()),
                [
                    'handler' => 'WordsPopularityScoreSetMessageHandler',
                    'exception' => $e
                ]
            );

            throw $e;
        }
    }

}