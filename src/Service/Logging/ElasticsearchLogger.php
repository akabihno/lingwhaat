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
            $now = new \DateTime();
            $indexName = sprintf('%s-%s', $this->indexPrefix, $now->format('Y.m.d'));

            $document = new Document(null, [
                'timestamp' => $now->format('c'),
                'level' => $level,
                'message' => $message,
                'context' => $context,
            ]);

            $this->client->getIndex($indexName)->addDocument($document);
            $this->client->getIndex($indexName)->refresh();
        } catch (\Throwable $e) {
            error_log(sprintf(
                'Failed to log to Elasticsearch: %s. Original message: %s',
                $e->getMessage(),
                $message
            ));
        }
    }

}