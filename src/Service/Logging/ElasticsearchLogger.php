<?php

namespace App\Service\Logging;

use Elastica\Client;
use Elastica\Document;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class ElasticsearchLogger implements LoggerInterface
{
    private string $indexPrefix;
    private Client $client;

    public function __construct(Client $client, string $indexPrefix = 'application-logs')
    {
        $this->client = $client;
        $this->indexPrefix = $indexPrefix;
    }

    #[\Override]
    public function emergency($message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    #[\Override]
    public function alert($message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    #[\Override]
    public function critical($message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    #[\Override]
    public function error($message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    #[\Override]
    public function warning($message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    #[\Override]
    public function notice($message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    #[\Override]
    public function info($message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    #[\Override]
    public function debug($message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    #[\Override]
    public function log($level, $message, array $context = []): void
    {
        try {
            $now = new \DateTime();
            $indexName = sprintf('%s-%s', $this->indexPrefix, $now->format('Y.m.d'));

            $document = new Document(null, [
                'timestamp' => $now->format('c'),
                'level' => $level,
                'message' => $message,
                'context' => $context,
            ]);

            // Intentionally no refresh() — per-doc refresh is a major throughput killer when many
            // workers log frequently. Elasticsearch refreshes automatically (~1s default), which
            // is fine for log visibility.
            $this->client->getIndex($indexName)->addDocument($document);
        } catch (\Throwable $e) {
            error_log(sprintf(
                'Failed to log to Elasticsearch: %s. Original message: %s',
                $e->getMessage(),
                $message
            ));
        }
    }

}