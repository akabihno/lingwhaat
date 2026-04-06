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
    private const string INDEX_NAME = 'wikipedia_global_patterns';
    private const int DEFAULT_WINDOW_SIZE = 18;
    private const int BASE = 101;
    private const int MOD = 1000000007;

    public function __construct()
    {
        $this->esClient = ElasticsearchClientFactory::create();
    }

    /**
     * Search for a cipher pattern in the global concatenated index.
     */
    public function search(string $cipherText, int $limit = 50, int $windowSize = self::DEFAULT_WINDOW_SIZE): array
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

        $index = $this->esClient->getIndex(self::INDEX_NAME);

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
     * Format results from the global index.
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
            ];
        }

        return $formatted;
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
