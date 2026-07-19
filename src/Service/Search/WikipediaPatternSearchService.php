<?php

namespace App\Service\Search;

use App\Constant\PatternIndexConstants;
use App\Service\Logging\ElasticsearchLogger;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Elastica\Client;
use Elastica\Multi\Search as MultiSearch;
use Elastica\Query;
use Elastica\Query\BoolQuery;
use Elastica\Query\Term;
use Elastica\Search;
use InvalidArgumentException;

class WikipediaPatternSearchService
{
    private const string LOG_SERVICE = '[WikipediaPatternSearchService]';
    private const string INDEX_NAME_PREFIX = 'wikipedia_global_patterns';
    // Must match the window size the corpus was indexed with — search filters length = windowSize,
    // so a mismatch silently returns zero hits.
    public const int DEFAULT_WINDOW_SIZE = PatternIndexConstants::WINDOW_SIZE;
    private const int BASE = 101;
    private const int MOD = 1000000007;

    public function __construct(
        private readonly Client $esClient,
        private readonly ElasticsearchLogger $logger,
    ) {
    }

    /**
     * Search a Wikipedia patterns index by ordered lists of symbols — either single characters
     * (legacy per-letter search) or grouped multi-character tokens (manuscript character grouping) —
     * running every window in one Elasticsearch _msearch round-trip instead of one request per
     * window. The canonical pattern and the length filter are both derived from each symbol
     * sequence, so a window of N symbols matches indexed corpus windows of length N. Callers build
     * the symbol lists with {@see ManuscriptWindowTokenizer::tokenize()} (empty groupings ⇒ one
     * symbol per character). If $languageCode is given the per-language index is searched, otherwise
     * all `wikipedia_global_patterns_*` indices.
     *
     * Returns results keyed by the same keys as $symbolWindows (order preserved). A window that
     * cannot be built (e.g. empty), a sub-search whose response errored, or a transport-level
     * failure of the whole _msearch all yield an empty array for the affected key(s); errors are
     * logged so a saturated ES that silently voids windows stays visible.
     *
     * @param array<int|string, array<int, string>> $symbolWindows
     * @return array<int|string, array<int, array<string, mixed>>>
     */
    public function searchByPatterns(array $symbolWindows, int $limit = 50, ?string $languageCode = null): array
    {
        // Seed every requested key so callers always get an entry back, even for windows we skip
        // (unbuildable) or that error server-side.
        $out = [];
        foreach ($symbolWindows as $key => $_) {
            $out[$key] = [];
        }
        if ($out === []) {
            return $out;
        }

        $indexName = $this->indexNameFor($languageCode);
        $multiSearch = new MultiSearch($this->esClient);
        // Searches are added unkeyed (insertion order) because Multi\Search::addSearch treats a
        // falsy key such as 0/"0" as "no key"; we map results back positionally instead.
        $keyOrder = [];
        foreach ($symbolWindows as $key => $symbols) {
            try {
                $query = $this->buildPatternQuery($symbols, $limit);
            } catch (InvalidArgumentException) {
                // Unbuildable window: keep its seeded empty result and skip it rather than
                // failing the whole batch. (buildPatternQuery is the single owner of this rule.)
                continue;
            }
            $search = new Search($this->esClient);
            $search->addIndexByName($indexName);
            $search->setQuery($query);
            $multiSearch->addSearch($search);
            $keyOrder[] = $key;
        }

        if ($keyOrder === []) {
            return $out;
        }

        try {
            $multiResultSet = $multiSearch->search();
        } catch (ClientResponseException | ServerResponseException $e) {
            $this->logger->error(
                sprintf('_msearch failed, voiding %d windows: %s', count($keyOrder), $e->getMessage()),
                ['service' => self::LOG_SERVICE, 'languageCode' => $languageCode]
            );

            return $out;
        }

        $resultSets = array_values($multiResultSet->getResultSets());
        $errorCount = 0;
        foreach ($keyOrder as $i => $key) {
            $resultSet = $resultSets[$i] ?? null;
            if ($resultSet === null || $resultSet->getResponse()->hasError()) {
                $errorCount++;
                continue;
            }
            $out[$key] = $this->formatResults($resultSet->getResults());
        }

        if ($errorCount > 0) {
            $this->logger->warning(
                sprintf('%d of %d _msearch sub-searches errored (voided windows treated as no-match)', $errorCount, count($keyOrder)),
                ['service' => self::LOG_SERVICE, 'languageCode' => $languageCode]
            );
        }

        return $out;
    }

    private function indexNameFor(?string $languageCode): string
    {
        return $languageCode === null
            ? self::INDEX_NAME_PREFIX . '_*'
            : self::INDEX_NAME_PREFIX . '_' . $languageCode;
    }

    /**
     * Build the isomorph query for a single ordered symbol window: filter on the canonical
     * pattern, its rolling hash and its length, sorted into stable corpus reading order.
     *
     * @param array<int, string> $symbols
     */
    private function buildPatternQuery(array $symbols, int $limit): Query
    {
        $length = count($symbols);
        if ($length <= 0) {
            throw new InvalidArgumentException('Symbol list must not be empty.');
        }

        $pattern = $this->buildPattern($symbols);
        $patternStr = implode(',', $pattern);
        $patternHash = $this->patternHash($pattern);

        $bool = new BoolQuery();

        $patternHashQuery = new Term();
        $patternHashQuery->setTerm('pattern_hash', $patternHash);
        $bool->addFilter($patternHashQuery);

        $lengthQuery = new Term();
        $lengthQuery->setTerm('length', $length);
        $bool->addFilter($lengthQuery);

        $patternQuery = new Term();
        $patternQuery->setTerm('pattern.keyword', $patternStr);
        $bool->addFilter($patternQuery);

        $query = new Query($bool);
        $query->setSize($limit);
        // True corpus reading order: the indexer walks articles by ascending id and emits windows
        // by ascending position, so (article_id, local_position) is a stable, batch-independent,
        // idempotent total order. (The old global_position counter reset per batch and collided
        // across the now-accumulated stable index.)
        $query->setSort([
            ['article_id' => 'asc'],
            ['local_position' => 'asc'],
        ]);

        return $query;
    }

    /**
     * Format results from the per-language indices. Each hit carries the language code derived
     * from its source index name so callers don't have to look it up via article_id.
     */
    private function formatResults(array $results): array
    {
        $formatted = [];

        foreach ($results as $hit) {
            $src = $hit->getSource();

            $formatted[] = [
                'article_id' => $src['article_id'] ?? null,
                'local_position' => $src['local_position'] ?? null,
                'pattern' => $src['pattern'] ?? null,
                'length' => $src['length'] ?? null,
                'pattern_hash' => $src['pattern_hash'] ?? null,
                'language_code' => self::languageCodeFromIndexName((string) $hit->getIndex()),
            ];
        }

        return $formatted;
    }

    private static function languageCodeFromIndexName(string $indexName): ?string
    {
        $prefix = self::INDEX_NAME_PREFIX . '_';
        if (!str_starts_with($indexName, $prefix)) {
            return null;
        }
        return substr($indexName, strlen($prefix)) ?: null;
    }

    /**
     * Reduce an ordered symbol list to its canonical isomorph pattern (each symbol replaced by its
     * first-appearance rank). Symbols are whole tokens, so a grouped ligature counts as one
     * position exactly like a single character does.
     *
     * @param array<int, string> $symbols
     * @return array<int, int>
     */
    private function buildPattern(array $symbols): array
    {
        $map = [];
        $nextId = 0;
        $pattern = [];

        foreach ($symbols as $symbol) {
            if (!isset($map[$symbol])) {
                $map[$symbol] = $nextId++;
            }

            $pattern[] = $map[$symbol];
        }

        return $pattern;
    }

    /**
     * @param array<int, int> $pattern
     */
    private function patternHash(array $pattern): int
    {
        $hash = 0;

        foreach ($pattern as $value) {
            $hash = (($hash * self::BASE) + $value) % self::MOD;
        }

        return $hash;
    }
}
