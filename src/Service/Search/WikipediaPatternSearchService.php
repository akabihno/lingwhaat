<?php

namespace App\Service\Search;

use Elastica\Client;
use Elastica\Query;
use Elastica\Query\BoolQuery;
use Elastica\Query\Term;
use Elastica\Query\MatchPhrase;

class WikipediaPatternSearchService
{
    private Client $esClient;
    private string $indexName = 'wikipedia_global_patterns';

    public function __construct()
    {
        $this->esClient = ElasticsearchClientFactory::create();
    }

    /**
     * Search for a cipher pattern in the global concatenated index.
     */
    public function search(string $cipherText, int $limit = 50): array
    {
        $normalized = $this->normalize($cipherText);
        $pattern = $this->buildPattern($normalized);
        $patternStr = implode(',', $pattern);
        $patternHash = $this->patternHash($pattern);

        $index = $this->esClient->getIndex($this->indexName);

        $bool = new BoolQuery();

        // Fast exact hash match
        $hashQuery = new Term();
        $hashQuery->setTerm('pattern_hash', $patternHash);
        $bool->addShould($hashQuery);

        // Fallback exact pattern match
        $patternQuery = new MatchPhrase();
        $patternQuery->setFieldQuery('pattern', $patternStr);
        $bool->addShould($patternQuery);

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
        return preg_replace('/[^\p{L}]+/u', '', $s);
    }

    private function buildPattern(string $s): array
    {
        $map = [];
        $nextId = 0;
        $pattern = [];

        foreach (preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY) as $ch) {
            if (!isset($map[$ch])) {
                $map[$ch] = $nextId++;
            }
            $pattern[] = $map[$ch];
        }

        return $pattern;
    }

    private function patternHash(array $pattern, int $base = 101, int $mod = 1000000007): int
    {
        $m = count($pattern);
        $hash = 0;

        for ($i = 0; $i < $m; $i++) {
            $power = $m - 1 - $i;
            $hash = ($hash + $pattern[$i] * $this->powmod($base, $power, $mod)) % $mod;
        }

        return $hash;
    }

    private function powmod(int $base, int $exp, int $mod): int
    {
        $result = 1;
        $base %= $mod;

        while ($exp > 0) {
            if ($exp & 1) {
                $result = ($result * $base) % $mod;
            }
            $base = ($base * $base) % $mod;
            $exp >>= 1;
        }

        return $result;
    }
}
