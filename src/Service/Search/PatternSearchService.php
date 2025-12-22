<?php

namespace App\Service\Search;

use App\Service\Logging\ElasticsearchLogger;
use Elastica\Client;
use Elastica\Query;
use Elastica\Query\Regexp;
use Elastica\Query\Wildcard;
use Exception;

class PatternSearchService
{
    private Client $esClient;
    private string $indexName = 'words_index';

    public function __construct(
        protected ElasticsearchLogger $logger,
    ) {
        $this->esClient = ElasticsearchClientFactory::create();
    }

    /**
     * Search for words matching a pattern where '?' represents a single unknown character.
     *
     * @param string $pattern Pattern to search (e.g., "h?s?" matches "hose", "hash", etc.)
     * @param string|null $languageCode Optional language filter
     * @param int $limit Maximum number of results to return
     * @return array Array of matching words with their IPA and language code
     */
    public function findByPattern(string $pattern, ?string $languageCode = null, int $limit = 100): array
    {
        if (empty($pattern)) {
            return [];
        }

        try {
            $wildcardQuery = new Wildcard('word', strtolower($pattern));

            $query = new Query\BoolQuery();
            $query->addMust($wildcardQuery);

            if ($languageCode !== null) {
                $languageQuery = new Query\Term();
                $languageQuery->setTerm('languageCode', $languageCode);
                $query->addMust($languageQuery);
            }

            $mainQuery = new Query($query);
            $mainQuery->setSize($limit);

            $this->logger->info('Pattern search initiated', [
                'service' => '[PatternSearchService]',
                'pattern' => $pattern,
                'languageCode' => $languageCode,
                'limit' => $limit,
            ]);

            $results = $this->esClient->getIndex($this->indexName)->search($mainQuery);

            $result = array_map(function ($r) {
                return $r->getSource();
            }, $results->getResults());

            $this->logger->info('Pattern search completed', [
                'service' => '[PatternSearchService]',
                'pattern' => $pattern,
                'result_count' => count($result),
            ]);

            return $result;
        } catch (Exception $e) {
            $this->logger->error('Pattern search failed', [
                'service' => '[PatternSearchService]',
                'pattern' => $pattern,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Search for words matching a pattern in the IPA field.
     *
     * @param string $pattern IPA pattern to search (e.g., "h?s?" for IPA representations)
     * @param string|null $languageCode Optional language filter
     * @param int $limit Maximum number of results to return
     * @return array Array of matching words with their IPA and language code
     */
    public function findByIpaPattern(string $pattern, ?string $languageCode = null, int $limit = 100): array
    {
        if (empty($pattern)) {
            return [];
        }

        try {
            $wildcardQuery = new Wildcard('ipa', strtolower($pattern));

            $query = new Query\BoolQuery();
            $query->addMust($wildcardQuery);

            if ($languageCode !== null) {
                $languageQuery = new Query\Term();
                $languageQuery->setTerm('languageCode', $languageCode);
                $query->addMust($languageQuery);
            }

            $mainQuery = new Query($query);
            $mainQuery->setSize($limit);

            $this->logger->info('IPA pattern search initiated', [
                'service' => '[PatternSearchService]',
                'ipa_pattern' => $pattern,
                'languageCode' => $languageCode,
                'limit' => $limit,
            ]);

            $results = $this->esClient->getIndex($this->indexName)->search($mainQuery);

            $result = array_map(function ($r) {
                return $r->getSource();
            }, $results->getResults());

            $this->logger->info('IPA pattern search completed', [
                'service' => '[PatternSearchService]',
                'ipa_pattern' => $pattern,
                'result_count' => count($result),
            ]);

            return $result;
        } catch (Exception $e) {
            $this->logger->error('IPA pattern search failed', [
                'service' => '[PatternSearchService]',
                'ipa_pattern' => $pattern,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Search for words matching advanced positional patterns.
     *
     * @param array $samePositions Array of position groups where positions must have the same character
     *                             Example: [[1,3,6], [2,4]] means positions 1,3,6 are same AND positions 2,4 are same
     * @param array $fixedChars Associative array of position => character for fixed positions
     *                          Example: [2 => 'o', 5 => 'x'] means position 2 must be 'o' and position 5 must be 'x'
     * @param string|null $languageCode Optional language filter
     * @param int $limit Maximum number of results to return
     * @return array Array of matching words with their IPA and language code
     */
    public function findByAdvancedPattern(
        array $samePositions = [],
        array $fixedChars = [],
        ?string $languageCode = null,
        int $limit = 100
    ): array {
        if (empty($samePositions) && empty($fixedChars)) {
            return [];
        }

        try {
            $regexpPattern = $this->buildRegexpPattern($samePositions, $fixedChars);

            $regexpQuery = new Regexp('word', $regexpPattern);

            $query = new Query\BoolQuery();
            $query->addMust($regexpQuery);

            if ($languageCode !== null) {
                $languageQuery = new Query\Term();
                $languageQuery->setTerm('languageCode', $languageCode);
                $query->addMust($languageQuery);
            }

            $mainQuery = new Query($query);
            $mainQuery->setSize($limit);

            $this->logger->info('Advanced pattern search initiated', [
                'service' => '[PatternSearchService]',
                'regexp_pattern' => $regexpPattern,
                'same_positions' => $samePositions,
                'fixed_chars' => $fixedChars,
                'languageCode' => $languageCode,
                'limit' => $limit,
            ]);

            $results = $this->esClient->getIndex($this->indexName)->search($mainQuery);

            $result = array_map(function ($r) {
                return $r->getSource();
            }, $results->getResults());

            $this->logger->info('Advanced pattern search completed', [
                'service' => '[PatternSearchService]',
                'regexp_pattern' => $regexpPattern,
                'result_count' => count($result),
            ]);

            return $result;
        } catch (Exception $e) {
            $this->logger->error('Advanced pattern search failed', [
                'service' => '[PatternSearchService]',
                'same_positions' => $samePositions,
                'fixed_chars' => $fixedChars,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Builds a regular expression pattern based on positional constraints.
     *
     * @param array $samePositions Groups of positions that must contain the same character
     * @param array $fixedChars Fixed characters at specific positions
     * @return string Regular expression pattern for Elasticsearch
     */
    private function buildRegexpPattern(array $samePositions, array $fixedChars): string
    {
        $positionMap = [];
        $captureGroupCount = 0;

        foreach ($samePositions as $group) {
            if (empty($group)) {
                continue;
            }

            sort($group);
            $captureGroupCount++;

            foreach ($group as $position) {
                if ($position < 1) {
                    continue;
                }
                $positionMap[$position] = [
                    'type' => 'capture',
                    'group' => $captureGroupCount,
                    'isFirst' => $position === $group[0],
                ];
            }
        }

        foreach ($fixedChars as $position => $char) {
            if ($position < 1) {
                continue;
            }
            $positionMap[$position] = [
                'type' => 'fixed',
                'char' => strtolower($char),
            ];
        }

        if (empty($positionMap)) {
            return '.*';
        }

        $maxPosition = max(array_keys($positionMap));
        $pattern = '';

        for ($i = 1; $i <= $maxPosition; $i++) {
            if (isset($positionMap[$i])) {
                $constraint = $positionMap[$i];

                if ($constraint['type'] === 'fixed') {
                    $pattern .= preg_quote($constraint['char'], '/');
                } elseif ($constraint['type'] === 'capture') {
                    if ($constraint['isFirst']) {
                        $pattern .= '(.)';
                    } else {
                        $pattern .= '\\' . $constraint['group'];
                    }
                }
            } else {
                $pattern .= '.';
            }
        }

        $pattern .= '.*';

        return $pattern;
    }
}
