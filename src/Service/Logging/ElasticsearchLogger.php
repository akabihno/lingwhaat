<?php

namespace App\Service\Logging;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Application logger. Despite the name (kept to avoid churning its many call sites), it no
 * longer writes to Elasticsearch: it forwards to the Monolog "pipeline" channel, which prod
 * sends to stderr as JSON. Logging one HTTP request per line to the single-node ES was a large
 * share of its write load and made every worker block on ES just to log.
 */
class ElasticsearchLogger implements LoggerInterface
{
    public function __construct(private readonly LoggerInterface $logger)
    {
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
        $this->logger->log($level, $message, $context);
    }

}