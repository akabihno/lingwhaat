<?php

namespace App\Service\Metrics;

use Prometheus\CollectorRegistry;
use Prometheus\Counter;
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

    /**
     * A monotonic counter stored in Redis. Unlike the gauges written by the stats command, counters
     * are not wiped between runs — they accumulate across worker restarts for the storage lifetime.
     *
     * @param array<string> $labelNames
     */
    public function counter(string $name, string $help, array $labelNames): Counter
    {
        return $this->registry->getOrRegisterCounter(self::NAMESPACE, $name, $help, $labelNames);
    }

    /**
     * Clears the metrics that are fully replaced on each run (the canonical-pattern overlap gauges,
     * for example) so stale label combinations from previous runs don't keep getting scraped —
     * while preserving the monotonic counters the pipelines write.
     *
     * The storage adapter can only wipe everything at once, so counters are snapshotted and
     * re-applied afterwards. An increment landing inside that window is lost; counters are only
     * ever read as rates, so a single missed increment is not worth locking for.
     */
    public function wipeGauges(): void
    {
        $counters = [];
        foreach ($this->registry->getMetricFamilySamples() as $family) {
            if ($family->getType() !== Counter::TYPE) {
                continue;
            }

            foreach ($family->getSamples() as $sample) {
                $counters[] = [
                    'name' => $family->getName(),
                    'help' => $family->getHelp(),
                    'labelNames' => $family->getLabelNames(),
                    'labelValues' => $sample->getLabelValues(),
                    // getValue() hands back the Redis string; cast so incBy() takes the float path
                    // rather than passing a string to Redis' integer HINCRBY.
                    'value' => (float) $sample->getValue(),
                ];
            }
        }

        $this->registry->wipeStorage();

        foreach ($counters as $counter) {
            // Empty namespace on purpose: getMetricFamilySamples() already reports the namespaced
            // name, and passing NAMESPACE again would prepend it twice.
            $this->registry
                ->getOrRegisterCounter('', $counter['name'], $counter['help'], $counter['labelNames'])
                ->incBy($counter['value'], $counter['labelValues']);
        }
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
