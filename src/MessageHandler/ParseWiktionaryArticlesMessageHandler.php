<?php

namespace App\MessageHandler;

use App\Message\ParseWiktionaryArticlesMessage;
use App\Service\WiktionaryArticlesIpaParserService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ParseWiktionaryArticlesMessageHandler
{
    public function __construct(
        private readonly WiktionaryArticlesIpaParserService $parserService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(ParseWiktionaryArticlesMessage $message): void
    {
        $language = $message->getLanguage();
        $limit = $message->getLimit();

        $this->logger->info(
            sprintf('Starting to parse Wiktionary articles for language: %s (limit: %d)', $language, $limit),
            ['handler' => 'ParseWiktionaryArticlesMessageHandler']
        );

        try {
            $this->parserService->run($language, $limit);

            $this->logger->info(
                sprintf('Successfully completed parsing for language: %s', $language),
                ['handler' => 'ParseWiktionaryArticlesMessageHandler']
            );
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf('Failed to parse articles for language: %s. Error: %s', $language, $e->getMessage()),
                [
                    'handler' => 'ParseWiktionaryArticlesMessageHandler',
                    'exception' => $e
                ]
            );

            throw $e; // Re-throw to let Messenger handle retry logic
        }
    }
}
