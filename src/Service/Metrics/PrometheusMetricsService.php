<?php

namespace App\Service\Metrics;

use Prometheus\CollectorRegistry;
use Prometheus\Gauge;
use Prometheus\RenderTextFormat;
use Prometheus\Storage\Redis;

class PrometheusMetricsService
{
    public const string NAMESPACE = 'lingwhaat';
    private const string DEFAULT_REDIS_HOST = '127.0.0.1';
    private const int DEFAULT_REDIS_PORT = 6379;

    private CollectorRegistry $registry;

    public function __construct(string $redisDsn)
    {
        $parsed = parse_url($redisDsn) ?: [];

        Redis::setDefaultOptions([
            'host' => $parsed['host'] ?? self::DEFAULT_REDIS_HOST,
            'port' => (int) ($parsed['port'] ?? self::DEFAULT_REDIS_PORT),
            'password' => $parsed['pass'] ?? null,
            'timeout' => 0.5,
            'read_timeout' => 5,
            'persistent_connections' => false,
            'prefix' => 'PROMETHEUS_',
        ]);

        $this->registry = new CollectorRegistry(new Redis());
    }

    /**
     * @param array<string> $labelNames
     */
    public function gauge(string $name, string $help, array $labelNames): Gauge
    {
        return $this->registry->getOrRegisterGauge(self::NAMESPACE, $name, $help, $labelNames);
    }

    public function render(): string
    {
        $renderer = new RenderTextFormat();
        return $renderer->render($this->registry->getMetricFamilySamples());
    }

    public function contentType(): string
    {
        return RenderTextFormat::MIME_TYPE;
    }
}
