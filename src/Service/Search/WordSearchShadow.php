<?php

namespace App\Service\Search;

use App\Constant\WordSearchBackend;
use App\Service\Logging\ElasticsearchLogger;
use App\Service\Metrics\MetricName;
use App\Service\Metrics\PrometheusMetricsService;

/**
 * Routes a word lookup to Elasticsearch, MySQL, or both (shadow mode), per WORD_LOOKUP_BACKEND.
 *
 * In shadow mode the caller always gets the Elasticsearch answer; the MySQL answer is computed
 * alongside, compared, and the outcome counted in word_search_shadow_total{shape,outcome}, with
 * every disagreement logged. A MySQL failure in shadow mode is counted, never surfaced.
 *
 * Outcomes:
 *  - same: identical results (fuzzy: same entries, in any order).
 *  - es_capped: (exact) MySQL found every language ES did, plus more, and ES hit its size cap.
 *  - same_languages: (fuzzy) different entries but the same languages, as many times each, which
 *    is what language detection counts.
 *  - missing: (exact) ES found a language MySQL did not.
 *  - extra: (exact) MySQL found more languages and ES was not capped.
 *  - different: anything else.
 *  - error: the MySQL lookup threw.
 */
class WordSearchShadow
{
    private const string LOG_SERVICE = '[WordSearchShadow]';
    public const string SHAPE_EXACT_WORD = 'exact_word';
    public const string SHAPE_EXACT_IPA = 'exact_ipa';
    public const string SHAPE_FUZZY_WORD = 'fuzzy_word';
    public const string SHAPE_FUZZY_IPA = 'fuzzy_ipa';
    public const string SHAPE_EXISTS_WORD = 'exists_word';

    public function __construct(
        private readonly ElasticsearchLogger $logger,
        private readonly PrometheusMetricsService $metrics,
        private readonly string $wordLookupBackend = WordSearchBackend::ELASTICSEARCH,
    ) {
    }

    /**
     * @template T
     * @param callable(): T $elasticsearch
     * @param callable(): T $mysql
     * @param callable(T, T): string $compare returns the outcome
     * @param array<string, mixed> $query logged with disagreements
     * @return T
     */
    public function serve(string $shape, array $query, callable $elasticsearch, callable $mysql, callable $compare): mixed
    {
        if ($this->wordLookupBackend === WordSearchBackend::MYSQL) {
            return $mysql();
        }
        if ($this->wordLookupBackend !== WordSearchBackend::SHADOW) {
            return $elasticsearch();
        }

        $esResult = $elasticsearch();
        try {
            $start = microtime(true);
            $mysqlResult = $mysql();
            $mysqlMs = (int) round((microtime(true) - $start) * 1000);
            $outcome = $compare($esResult, $mysqlResult);
        } catch (\Throwable $e) {
            $this->record($shape, 'error');
            $this->logger->warning(sprintf('MySQL %s lookup failed: %s', $shape, $e->getMessage()), ['service' => self::LOG_SERVICE, 'query' => $query]);

            return $esResult;
        }

        $this->record($shape, $outcome);
        if ($outcome !== 'same') {
            $this->logger->info(sprintf('Shadow %s: %s', $shape, $outcome), [
                'service' => self::LOG_SERVICE,
                'query' => $query,
                'mysqlMs' => $mysqlMs,
                'es' => self::summary($esResult),
                'mysql' => self::summary($mysqlResult),
            ]);
        }

        return $esResult;
    }

    /**
     * @param list<array<string, mixed>> $es
     * @param list<array<string, mixed>> $mysql
     */
    public static function compareExact(array $es, array $mysql, int $esCap): string
    {
        $esLanguages = array_unique(array_column($es, 'languageCode'));
        $mysqlLanguages = array_column($mysql, 'languageCode');
        sort($esLanguages);
        sort($mysqlLanguages);

        if ($esLanguages === $mysqlLanguages) {
            return 'same';
        }
        if (array_diff($esLanguages, $mysqlLanguages) !== []) {
            return 'missing';
        }

        return count($es) >= $esCap ? 'es_capped' : 'extra';
    }

    /**
     * @param list<array<string, mixed>> $es
     * @param list<array<string, mixed>> $mysql
     */
    public static function compareFuzzy(array $es, array $mysql): string
    {
        $entries = static fn (array $docs): array => array_map(static fn (array $d): string => $d['languageCode'] . ':' . $d['word'], $docs);
        $languages = static fn (array $docs): array => array_column($docs, 'languageCode');

        $a = $entries($es);
        $b = $entries($mysql);
        sort($a);
        sort($b);
        if ($a === $b) {
            return 'same';
        }

        $la = $languages($es);
        $lb = $languages($mysql);
        sort($la);
        sort($lb);

        return $la === $lb ? 'same_languages' : 'different';
    }

    public static function compareExists(bool $es, bool $mysql): string
    {
        return $es === $mysql ? 'same' : 'different';
    }

    private function record(string $shape, string $outcome): void
    {
        $this->metrics
            ->counter(MetricName::WORD_SEARCH_SHADOW_TOTAL, 'Word lookups run on both Elasticsearch and MySQL, by query shape and comparison outcome.', ['shape', 'outcome'])
            ->inc([$shape, $outcome]);
    }

    /**
     * Compact form of a result for the disagreement log.
     */
    private static function summary(mixed $result): mixed
    {
        if (!is_array($result)) {
            return $result;
        }

        return array_map(static fn (array $d): string => $d['languageCode'] . ':' . $d['word'], array_slice($result, 0, 20));
    }
}
