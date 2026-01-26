<?php

namespace App\MessageHandler;

use App\Message\ParseWikipediaArticlesMessage;
use App\Service\WikipediaPatternParserService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ParseWikipediaArticlesMessageHandler
{
    public function __construct(
        private readonly WikipediaPatternParserService $parserService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(ParseWikipediaArticlesMessage $message): void
    {
        $languageCode = $message->getLanguageCode();
        $limit = $message->getLimit();

        $this->logger->info(
            sprintf('Starting to parse Wikipedia articles for language: %s (limit: %d)', $languageCode, $limit),
            ['handler' => 'ParseWikipediaArticlesMessageHandler']
        );

        try {
            $this->parserService->run($languageCode, $limit);

            $this->logger->info(
                sprintf('Successfully completed parsing for language: %s', $languageCode),
                ['handler' => 'ParseWikipediaArticlesMessageHandler']
            );
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf('Failed to parse articles for language: %s. Error: %s', $languageCode, $e->getMessage()),
                [
                    'handler' => 'ParseWikipediaArticlesMessageHandler',
                    'exception' => $e,
                ]
            );

            throw $e;
        }
    }
}
