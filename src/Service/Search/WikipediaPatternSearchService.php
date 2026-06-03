<?php

namespace App\Service\Search;

use Elastica\Client;
use Elastica\Query;
use Elastica\Query\BoolQuery;
use Elastica\Query\Term;
use InvalidArgumentException;

class WikipediaPatternSearchService
{
    private Client $esClient;
    private const string INDEX_NAME_PREFIX = 'wikipedia_global_patterns';
    public const int DEFAULT_WINDOW_SIZE = 18;
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

        $pattern = $this->buildPattern($normalized);
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
        $lengthQuery->setTerm('length', $windowSize);
        $bool->addFilter($lengthQuery);

        $patternQuery = new Term();
        $patternQuery->setTerm('pattern.keyword', $patternStr);
        $bool->addFilter($patternQuery);

        $query = new Query($bool);
        $query->setSize($limit);
        $query->setSort(['global_position' => 'asc']);

        $results = $index->search($query)->getResults();

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
                'global_position' => $src['global_position'] ?? null,
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
     * @return array<int, int>
     */
    private function buildPattern(string $s): array
    {
        $map = [];
        $nextId = 0;
        $pattern = [];

        foreach (preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY) as $ch) {
            if ($ch === false) {
                continue;
            }

            if (!isset($map[$ch])) {
                $map[$ch] = $nextId++;
            }

            $pattern[] = $map[$ch];
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
