<?php

namespace App\Service\Search;

use App\Service\Logging\ElasticsearchLogger;
use Elastica\Client;
use Elastica\Query;
use Elastica\Query\Regexp;
use Elastica\Query\Wildcard;
use Elastica\Script\Script;
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
            $scriptSource = $this->buildPainlessScript($samePositions, $fixedChars);

            $scriptQuery = new Query\Script(
                new Script($scriptSource)
            );

            $query = new Query\BoolQuery();
            $query->addFilter($scriptQuery);

            if ($languageCode !== null) {
                $languageQuery = new Query\Term();
                $languageQuery->setTerm('languageCode', $languageCode);
                $query->addMust($languageQuery);
            }

            $mainQuery = new Query($query);
            $mainQuery->setSize($limit);

            $this->logger->info('Advanced pattern search initiated', [
                'service' => '[PatternSearchService]',
                'script' => $scriptSource,
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
                'script' => $scriptSource,
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
     * Builds a Painless script for positional pattern matching.
     *
     * @param array $samePositions Groups of positions that must contain the same character
     * @param array $fixedChars Fixed characters at specific positions
     * @return string Painless script for Elasticsearch
     */
    private function buildPainlessScript(array $samePositions, array $fixedChars): string
    {
        $conditions = [];

        // Get the word field value
        $script = "String word = doc['word.keyword'].size() > 0 ? doc['word.keyword'].value.toLowerCase() : ''; ";

        // Check minimum length
        $maxPosition = 0;
        foreach ($samePositions as $group) {
            foreach ($group as $position) {
                if ($position > $maxPosition) {
                    $maxPosition = $position;
                }
            }
        }
        foreach ($fixedChars as $position => $char) {
            if ($position > $maxPosition) {
                $maxPosition = $position;
            }
        }

        if ($maxPosition > 0) {
            $conditions[] = "word.length() >= $maxPosition";
        }

        // Add same position constraints
        foreach ($samePositions as $groupIndex => $group) {
            if (empty($group) || count($group) < 2) {
                continue;
            }

            sort($group);
            $firstPos = $group[0] - 1; // Convert to 0-indexed

            for ($i = 1; $i < count($group); $i++) {
                $currentPos = $group[$i] - 1; // Convert to 0-indexed
                $conditions[] = "word.charAt($firstPos) == word.charAt($currentPos)";
            }
        }

        // Add fixed character constraints
        foreach ($fixedChars as $position => $char) {
            if ($position < 1) {
                continue;
            }
            $pos = $position - 1; // Convert to 0-indexed
            $escapedChar = addslashes(strtolower($char));
            $conditions[] = "word.charAt($pos) == '$escapedChar'";
        }

        if (empty($conditions)) {
            return "return true;";
        }

        $script .= "return " . implode(" && ", $conditions) . ";";

        return $script;
    }
}
