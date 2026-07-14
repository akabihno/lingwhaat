<?php

namespace App\Service\Metrics;

/**
 * Central registry of Prometheus metric names so every metric the app exposes is defined in one
 * place. These are the metric name only — PrometheusMetricsService prepends the
 * PrometheusMetricsService::NAMESPACE, so the scraped series is e.g.
 * "lingwhaat_wikipedia_articles_indexed_total".
 */
final class MetricName
{
    /**
     * Counter: total number of Wikipedia articles indexed into Elasticsearch by the pattern-index
     * pipeline. Labelled by language so per-language throughput is visible. Monotonically
     * increasing (per process/storage lifetime) — use rate() in Prometheus for throughput.
     */
    public const string WIKIPEDIA_ARTICLES_INDEXED_TOTAL = 'wikipedia_articles_indexed_total';

    /**
     * Gauge: Wikipedia occurrence count for canonical patterns shared between a language top-N and a
     * manuscript source top-N. Written by app:canonical-pattern-stats.
     */
    public const string CANONICAL_PATTERN_OVERLAP_WIKIPEDIA_COUNT = 'canonical_pattern_overlap_wikipedia_count';

    /**
     * Gauge: manuscript occurrence count for canonical patterns shared between a language top-N and a
     * manuscript source top-N. Written by app:canonical-pattern-stats.
     */
    public const string CANONICAL_PATTERN_OVERLAP_MANUSCRIPT_COUNT = 'canonical_pattern_overlap_manuscript_count';
}
