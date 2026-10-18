<?php

namespace App\Service\Search;

use App\Constant\WordSearchBackend;
use App\Service\Logging\ElasticsearchLogger;
use App\Service\Metrics\MetricName;
use App\Service\Metrics\PrometheusMetricsService;

/**
 * Routes a word lookup to Elasticsearch, MySQL, or both (shadow mode), per WORD_LOOKUP_BACKEND
 * (or the backend a caller passes, e.g. WORD_PATTERN_BACKEND for pattern searches).
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
 *  - both_capped: (wildcard/positional) both stores hit the size cap, so their (unordered) hits
 *    cannot be compared beyond that.
 *  - mysql_superset: (wildcard/positional) every ES hit is among MySQL's hits, plus more (MySQL
 *    has languages added after the words_index build).
 *  - mysql_capped: (wildcard/positional) ES's result was complete, MySQL hit the cap and its top-N
 *    leaves out some ES hits; not verifiable from the capped results.
 *  - different: anything else.
 *  - error: the MySQL lookup threw.
 */
class WordSearchShadow
{
    private const string LOG_SERVICE = '[WordSearchShadow]';
    public const string SHAPE_EXACT_WORD = 'exact_word';
    public const string SHAPE_FUZZY_WORD = 'fuzzy_word';
    public const string SHAPE_EXISTS_WORD = 'exists_word';
    public const string SHAPE_WILDCARD_WORD = 'wildcard_word';
    public const string SHAPE_WILDCARD_IPA = 'wildcard_ipa';
    public const string SHAPE_POSITIONAL = 'positional';
    // Whole /api/language detections: the language ES's lookups vote for vs MySQL's.
    public const string SHAPE_DETECTED_LANGUAGE = 'detected_language';

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
     * @param string|null $backend overrides WORD_LOOKUP_BACKEND
     * @param (callable(T|null): void)|null $onMysqlResult shadow mode only: receives the MySQL
     *        result (null if it failed), for callers that compare at a higher level
     * @return T
     */
    public function serve(string $shape, array $query, callable $elasticsearch, callable $mysql, callable $compare, ?string $backend = null, ?callable $onMysqlResult = null): mixed
    {
        $backend ??= $this->wordLookupBackend;
        if ($backend === WordSearchBackend::MYSQL) {
            return $mysql();
        }
        if ($backend !== WordSearchBackend::SHADOW) {
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
            if ($onMysqlResult !== null) {
                ($onMysqlResult)(null);
            }

            return $esResult;
        }

        if ($onMysqlResult !== null) {
            ($onMysqlResult)($mysqlResult);
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

    /**
     * Hits of one size-capped, unordered query (ES returned script/wildcard hits in index order).
     *
     * @param list<array<string, mixed>> $es
     * @param list<array<string, mixed>> $mysql
     */
    public static function compareCapped(array $es, array $mysql, int $size): string
    {
        $entries = static function (array $docs): array {
            $keys = array_map(static fn (array $d): string => $d['languageCode'] . ':' . $d['word'], $docs);
            sort($keys);
            return $keys;
        };
        $a = $entries($es);
        $b = $entries($mysql);

        if ($a === $b) {
            return 'same';
        }
        $esCapped = count($es) >= $size;
        $mysqlCapped = count($mysql) >= $size;
        if ($esCapped && $mysqlCapped) {
            return 'both_capped';
        }

        // Multiset containment: every ES hit (with multiplicity) is among MySQL's hits.
        $remaining = array_count_values($b);
        foreach ($a as $key) {
            if (($remaining[$key] ?? 0) === 0) {
                // MySQL's capped top-N may simply leave out some of ES's complete hits.
                return ($mysqlCapped && !$esCapped) ? 'mysql_capped' : 'different';
            }
            $remaining[$key]--;
        }

        return 'mysql_superset';
    }

    /**
     * A batch of capped queries: the worst per-query outcome.
     *
     * @param list<list<array<string, mixed>>> $es
     * @param list<list<array<string, mixed>>> $mysql
     * @param list<int> $sizes
     */
    public static function compareCappedBatch(array $es, array $mysql, array $sizes): string
    {
        if (count($es) !== count($mysql)) {
            return 'different';
        }

        $rank = ['same' => 0, 'both_capped' => 1, 'mysql_superset' => 2, 'mysql_capped' => 3, 'different' => 4];
        $worst = 'same';
        foreach ($sizes as $i => $size) {
            $outcome = self::compareCapped($es[$i], $mysql[$i], $size);
            if ($rank[$outcome] > $rank[$worst]) {
                $worst = $outcome;
            }
        }

        return $worst;
    }

    public static function compareExists(bool $es, bool $mysql): string
    {
        return $es === $mysql ? 'same' : 'different';
    }

    /**
     * Record an outcome that a caller determined itself (e.g. comparing whole detections); logged
     * unless "same".
     *
     * @param array<string, mixed> $context
     */
    public function recordComparison(string $shape, string $outcome, array $context): void
    {
        $this->record($shape, $outcome);
        if ($outcome !== 'same') {
            $this->logger->info(sprintf('Shadow %s: %s', $shape, $outcome), ['service' => self::LOG_SERVICE] + $context);
        }
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
        if ($result !== [] && is_array($result[0] ?? null) && !isset($result[0]['languageCode'])) {
            // A batch: hit counts per query.
            return array_map('count', $result);
        }

        return array_map(static fn (array $d): string => $d['languageCode'] . ':' . $d['word'], array_slice($result, 0, 20));
    }
}
