<?php

namespace App\Service\Search;

use App\Constant\PatternIndexConstants;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Elastica\Client;
use Elastica\Query;
use Elastica\Query\BoolQuery;
use Elastica\Query\Term;
use InvalidArgumentException;

class WikipediaPatternSearchService
{
    private Client $esClient;
    private const string INDEX_NAME_PREFIX = 'wikipedia_global_patterns';
    // Must match the window size the corpus was indexed with — search filters length = windowSize,
    // so a mismatch silently returns zero hits.
    public const int DEFAULT_WINDOW_SIZE = PatternIndexConstants::WINDOW_SIZE;
    private const int BASE = 101;
    private const int MOD = 1000000007;

    public function __construct(Client $esClient)
    {
        $this->esClient = $esClient;
    }

    /**
     * Search for a cipher pattern in a Wikipedia patterns index.
     * If $languageCode is provided, searches the per-language index; otherwise searches across all
     * per-language indices via `wikipedia_global_patterns_*` (Elastica multi-index syntax).
     */
    public function search(string $cipherText, int $limit = 50, int $windowSize = self::DEFAULT_WINDOW_SIZE, ?string $languageCode = null): array
    {
        if ($windowSize <= 0) {
            throw new InvalidArgumentException('windowSize must be greater than 0.');
        }

        $normalized = $this->normalize($cipherText);
        $normalizedLength = mb_strlen($normalized);
        if ($normalizedLength !== $windowSize) {
            throw new InvalidArgumentException('Search text length must match the window size.');
        }

        $symbols = preg_split('//u', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return $this->searchByPattern($symbols, $limit, $languageCode);
    }

    /**
     * Search by an explicit ordered list of symbols — either single characters (legacy per-letter
     * search) or grouped multi-character tokens (manuscript character grouping). The canonical
     * pattern and the length filter are both derived from the symbol sequence, so a window of N
     * grouped tokens matches indexed corpus windows of length N. {@see search()} is the special
     * case where every symbol is one character and N equals the window size.
     *
     * @param array<int, string> $symbols
     * @return array<int, array<string, mixed>>
     */
    public function searchByPattern(array $symbols, int $limit = 50, ?string $languageCode = null): array
    {
        $length = count($symbols);
        if ($length <= 0) {
            throw new InvalidArgumentException('Symbol list must not be empty.');
        }

        $pattern = $this->buildPattern($symbols);
        $patternStr = implode(',', $pattern);
        $patternHash = $this->patternHash($pattern);

        $indexName = $languageCode === null
            ? self::INDEX_NAME_PREFIX . '_*'
            : self::INDEX_NAME_PREFIX . '_' . $languageCode;
        $index = $this->esClient->getIndex($indexName);

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

        try {
            $results = $index->search($query)->getResults();
        } catch (ClientResponseException | ServerResponseException $e) {
            return [];
        }

        return $this->formatResults($results);
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

    private function normalize(string $s): string
    {
        $s = mb_strtolower($s);
        return preg_replace('/[^\p{L}]+/u', '', $s) ?? '';
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
