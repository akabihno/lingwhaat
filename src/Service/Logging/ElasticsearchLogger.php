<?php

namespace App\Service\Logging;

use Elastica\Client;
use Elastica\Document;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class ElasticsearchLogger implements LoggerInterface
{
    private string $indexName;
    private Client $client;

    public function __construct(Client $client, string $indexName = 'application-logs')
    {
        $this->client = $client;
        $this->indexName = $indexName;
    }

    public function emergency($message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    public function alert($message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    public function critical($message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function error($message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function warning($message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function notice($message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function info($message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function debug($message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    public function log($level, $message, array $context = []): void
    {
        try {
            $document = new Document(null, [
                'timestamp' => (new \DateTime())->format('c'),
                'level' => $level,
                'message' => $message,
                'context' => $context,
            ]);

            $this->client->getIndex($this->indexName)->addDocument($document);
            $this->client->getIndex($this->indexName)->refresh();
        } catch (\Throwable $e) {
            error_log(sprintf(
                'Failed to log to Elasticsearch: %s. Original message: %s',
                $e->getMessage(),
                $message
            ));
        }
    }

}