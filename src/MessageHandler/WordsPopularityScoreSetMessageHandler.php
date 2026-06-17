<?php

namespace App\MessageHandler;

use App\Message\WordsPopularityScoreSetMessage;
use App\Service\Logging\ElasticsearchLogger;
use App\Service\Search\WordsPopularityScoreSetService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class WordsPopularityScoreSetMessageHandler
{
    private const int PROCESSING_LIMIT = 200000;
    public function __construct(
        private readonly WordsPopularityScoreSetService $popularityScoreSetService,
        private readonly ElasticsearchLogger $logger
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

        if ($offset > self::PROCESSING_LIMIT) {
            // Not a failure — just "no more work for this language this cycle". ACK and return
            // so the message is DELETEd instead of INSERTed into the failed queue (which under
            // heavy load contends with normal polling and times out on lock wait).
            $this->logger->info(
                sprintf('Processing limit reached for %s at offset %d; skipping', $languageCode, $offset),
                ['service' => '[WordsPopularityScoreSetMessageHandler]']
            );
            return;
        }

        $this->logger->info(
            sprintf('Starting to parse Wikipedia articles for language: %s (limit: %d, offset %d)', $languageCode, $limit, $offset),
            ['service' => '[WordsPopularityScoreSetMessageHandler]']
        );

        try {
            $this->popularityScoreSetService->execute($languageCode, $limit, $offset);

            $this->logger->info(
                sprintf('Successfully completed parsing for language: %s', $languageCode),
                ['service' => '[WordsPopularityScoreSetMessageHandler]']
            );
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf('Failed to parse articles for language: %s. Error: %s', $languageCode, $e->getMessage()),
                [
                    'service' => '[WordsPopularityScoreSetMessageHandler]',
                    'exception' => $e
                ]
            );

            throw $e;
        }
    }

}