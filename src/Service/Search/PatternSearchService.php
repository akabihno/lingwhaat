<?php

namespace App\Service\Search;

use App\Service\Logging\ElasticsearchLogger;
use Elastica\Client;
use Elastica\Query;
use Elastica\Query\Wildcard;
use Elastica\Script\Script;
use Exception;

class PatternSearchService
{
    private const int MIN_COMMON_CHARS = 3;
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
     * @param int|null $exactLength Optional exact word length filter
     * @param string|null $languageCode Optional language filter
     * @param int $limit Maximum number of results to return
     * @return array Array of matching words with their IPA and language code
     */
    public function findByAdvancedPattern(
        array $samePositions = [],
        array $fixedChars = [],
        ?int $exactLength = null,
        ?string $languageCode = null,
        int $limit = 100
    ): array {
        if (empty($samePositions) && empty($fixedChars)) {
            return [];
        }

        try {
            $scriptSource = $this->buildPainlessScript($samePositions, $fixedChars, $exactLength);

            $script = new Script($scriptSource, null, 'painless');
            $scriptQuery = new Query\Script($script);

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
                'exact_length' => $exactLength,
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
                'exact_length' => $exactLength,
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
     * @param int|null $exactLength Optional exact word length
     * @return string Painless script for Elasticsearch
     */
    private function buildPainlessScript(array $samePositions, array $fixedChars, ?int $exactLength = null): string
    {
        // Get the word field value from keyword field (text fields don't have doc values)
        $script = "if (doc['word.keyword'].size() == 0) { return false; } ";
        $script .= "String word = doc['word.keyword'].value.toLowerCase(); ";

        // Check exact length if specified
        if ($exactLength !== null && $exactLength > 0) {
            $script .= "if (word.length() != $exactLength) { return false; } ";
        } else {
            // Check minimum length based on position constraints
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
                $script .= "if (word.length() < $maxPosition) { return false; } ";
            }
        }

        // Add same position constraints
        foreach ($samePositions as $groupIndex => $group) {
            if (empty($group) || count($group) < 2) {
                continue;
            }

            sort($group);
            $firstPos = $group[0] - 1; // Convert to 0-indexed

            // Check that all positions in the group have the same character
            for ($i = 1; $i < count($group); $i++) {
                $currentPos = $group[$i] - 1; // Convert to 0-indexed
                $script .= "if (word.charAt($firstPos) != word.charAt($currentPos)) { return false; } ";
            }

            // Store allowed positions for exclusivity checking
            // We'll check exclusivity after we know what character is at this position
            $allowedPositions = array_map(fn($p) => $p - 1, $group); // Convert to 0-indexed
            $positionsList = implode(',', $allowedPositions);

            // Check that this character appears ONLY at these positions
            $script .= "char c$groupIndex = word.charAt($firstPos); ";
            $script .= "for (int idx = 0; idx < word.length(); idx++) { ";
            $script .= "if (word.charAt(idx) == c$groupIndex) { ";
            $script .= "boolean found = false; ";
            $script .= "int[] allowed$groupIndex = new int[]{" . $positionsList . "}; ";
            $script .= "for (int allowedIdx : allowed$groupIndex) { ";
            $script .= "if (idx == allowedIdx) { found = true; break; } ";
            $script .= "} ";
            $script .= "if (!found) { return false; } ";
            $script .= "} ";
            $script .= "} ";
        }

        // Add fixed character constraints
        foreach ($fixedChars as $position => $char) {
            if ($position < 1) {
                continue;
            }
            $pos = $position - 1; // Convert to 0-indexed
            $escapedChar = addslashes(strtolower($char));
            $script .= "if (word.charAt($pos) != '$escapedChar') { return false; } ";

            // Ensure this fixed character appears ONLY at this position
            $script .= "for (int idx = 0; idx < word.length(); idx++) { ";
            $script .= "if (idx != $pos && word.charAt(idx) == '$escapedChar') { ";
            $script .= "return false; ";
            $script .= "} ";
            $script .= "} ";
        }

        // Additional check: Ensure no character repeats without being fully constrained
        // For any character that appears multiple times, all positions must be specified
        // Build a set of all constrained positions
        $allConstrainedPositions = [];
        foreach ($samePositions as $group) {
            foreach ($group as $pos) {
                $allConstrainedPositions[] = $pos - 1; // 0-indexed
            }
        }
        foreach ($fixedChars as $position => $char) {
            if ($position >= 1) {
                $allConstrainedPositions[] = $position - 1; // 0-indexed
            }
        }
        $constrainedPosList = implode(',', $allConstrainedPositions);

        if (!empty($allConstrainedPositions)) {
            $script .= "int[] constrainedPositions = new int[]{" . $constrainedPosList . "}; ";
            $script .= "for (int i = 0; i < word.length(); i++) { ";
            $script .= "char currentChar = word.charAt(i); ";
            $script .= "int count = 0; ";
            $script .= "for (int j = 0; j < word.length(); j++) { ";
            $script .= "if (word.charAt(j) == currentChar) { count++; } ";
            $script .= "} ";
            $script .= "if (count > 1) { ";
            $script .= "boolean isConstrained = false; ";
            $script .= "for (int cp : constrainedPositions) { ";
            $script .= "if (i == cp) { isConstrained = true; break; } ";
            $script .= "} ";
            $script .= "if (!isConstrained) { return false; } ";
            $script .= "} ";
            $script .= "} ";
        }

        $script .= "return true;";

        return $script;
    }

    /**
     * Find words that intersect across multiple pattern queries.
     * Returns only words from the same language that can overlap.
     *
     * @param array $intersections Array of pattern configurations
     * @param int $limit Maximum number of result groups to return
     * @return array Array of intersection groups
     */
    public function findIntersections(array $intersections, int $limit = 100): array
    {
        if (empty($intersections)) {
            return [];
        }

        try {
            $this->logger->info('Intersection search initiated', [
                'service' => '[PatternSearchService]',
                'intersection_count' => count($intersections),
                'limit' => $limit,
            ]);

            $patternResults = [];
            foreach ($intersections as $index => $pattern) {
                $samePositions = $pattern['samePositions'] ?? [];
                $fixedChars = $pattern['fixedChars'] ?? [];
                $exactLength = $pattern['exactLength'] ?? null;
                $languageCode = $pattern['languageCode'] ?? null;

                $results = $this->findByAdvancedPattern(
                    $samePositions,
                    $fixedChars,
                    $exactLength,
                    $languageCode,
                    1000 // Get more results to find intersections
                );

                $patternResults[$index] = $results;
            }

            $intersectionGroups = $this->findWordIntersections($patternResults);

            $intersectionGroups = array_slice($intersectionGroups, 0, $limit);

            $this->logger->info('Intersection search completed', [
                'service' => '[PatternSearchService]',
                'result_count' => count($intersectionGroups),
            ]);

            return $intersectionGroups;
        } catch (Exception $e) {
            $this->logger->error('Intersection search failed', [
                'service' => '[PatternSearchService]',
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Find intersecting words across multiple result sets.
     * Words intersect if one can be found within another at relative positions.
     *
     * @param array $patternResults Array of result sets from different patterns
     * @return array Array of intersection groups
     */
    private function findWordIntersections(array $patternResults): array
    {
        if (count($patternResults) < 2) {
            return [];
        }

        $intersectionGroups = [];

        $indices = array_keys($patternResults);
        $combinations = $this->getCombinations($indices, count($indices));

        foreach ($combinations as $combination) {
            $wordsByPattern = [];
            foreach ($combination as $patternIndex) {
                $wordsByPattern[$patternIndex] = $patternResults[$patternIndex];
            }

            $this->findIntersectingWordGroups($wordsByPattern, $intersectionGroups);
        }

        return $intersectionGroups;
    }

    /**
     * Find groups of words (one from each pattern) that intersect.
     *
     * @param array $wordsByPattern Words grouped by pattern index
     * @param array &$intersectionGroups Reference to store intersection groups
     */
    private function findIntersectingWordGroups(array $wordsByPattern, array &$intersectionGroups): void
    {
        $patternIndices = array_keys($wordsByPattern);
        $firstPatternIndex = $patternIndices[0];

        foreach ($wordsByPattern[$firstPatternIndex] as $firstWord) {
            $this->recursiveIntersectionSearch(
                $wordsByPattern,
                $patternIndices,
                1,
                [$firstWord],
                $intersectionGroups
            );
        }
    }

    /**
     * Recursively search for intersecting word combinations.
     *
     * @param array $wordsByPattern Words grouped by pattern index
     * @param array $patternIndices All pattern indices
     * @param int $currentIndex Current index in pattern array
     * @param array $currentGroup Current group of words being built
     * @param array &$intersectionGroups Reference to store intersection groups
     */
    private function recursiveIntersectionSearch(
        array $wordsByPattern,
        array $patternIndices,
        int $currentIndex,
        array $currentGroup,
        array &$intersectionGroups
    ): void {
        if ($currentIndex >= count($patternIndices)) {
            $languageCode = $currentGroup[0]['languageCode'];
            $allSameLanguage = true;
            foreach ($currentGroup as $word) {
                if ($word['languageCode'] !== $languageCode) {
                    $allSameLanguage = false;
                    break;
                }
            }

            if ($allSameLanguage && $this->doWordsIntersect($currentGroup)) {
                $intersectionGroups[] = [
                    'languageCode' => $languageCode,
                    'words' => array_map(fn($w) => [
                        'word' => $w['word'],
                        'ipa' => $w['ipa'] ?? null
                    ], $currentGroup)
                ];
            }
            return;
        }

        $patternIndex = $patternIndices[$currentIndex];
        foreach ($wordsByPattern[$patternIndex] as $word) {
            $newGroup = $currentGroup;
            $newGroup[] = $word;

            $intersectsWithAll = true;
            foreach ($currentGroup as $existingWord) {
                if (!$this->doTwoWordsIntersect($existingWord['word'], $word['word'])) {
                    $intersectsWithAll = false;
                    break;
                }
            }

            if ($intersectsWithAll) {
                $this->recursiveIntersectionSearch(
                    $wordsByPattern,
                    $patternIndices,
                    $currentIndex + 1,
                    $newGroup,
                    $intersectionGroups
                );
            }
        }
    }

    /**
     * Check if all words in a group intersect with each other.
     *
     * @param array $words Array of word objects
     * @return bool True if all words intersect
     */
    private function doWordsIntersect(array $words): bool
    {
        if (count($words) < 2) {
            return true;
        }

        for ($i = 0; $i < count($words) - 1; $i++) {
            for ($j = $i + 1; $j < count($words); $j++) {
                if (!$this->doTwoWordsIntersect($words[$i]['word'], $words[$j]['word'])) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Check if two words intersect (share common characters for cipher-solving).
     * Words intersect if they share at least minCommonChars distinct characters.
     * This helps in solving substitution ciphers by cross-referencing character mappings.
     *
     * Examples:
     * - "spear" {s,p,e,a,r} and "fear" {f,e,a,r} share {e,a,r} = 3 chars → intersect
     * - "spear" {s,p,e,a,r} and "arson" {a,r,s,o,n} share {a,r,s} = 3 chars → intersect
     * - "abba" {a,b} and "baab" {a,b} share {a,b} = 2 chars → don't intersect (< 3)
     *
     * @param string $word1 First word
     * @param string $word2 Second word
     * @return bool True if words intersect
     */
    private function doTwoWordsIntersect(string $word1, string $word2): bool
    {
        $word1Lower = strtolower($word1);
        $word2Lower = strtolower($word2);

        $chars1 = array_unique(str_split($word1Lower));
        $chars2 = array_unique(str_split($word2Lower));

        $commonChars = array_intersect($chars1, $chars2);

        return count($commonChars) >= self::MIN_COMMON_CHARS;
    }

    /**
     * Get all combinations of array elements.
     *
     * @param array $array Input array
     * @param int $length Length of combinations
     * @return array Array of combinations
     */
    private function getCombinations(array $array, int $length): array
    {
        if ($length === 0 || $length > count($array)) {
            return [];
        }

        if ($length === 1) {
            return array_map(fn($item) => [$item], $array);
        }

        if ($length === count($array)) {
            return [$array];
        }

        $combinations = [];
        $first = array_shift($array);

        $withFirst = $this->getCombinations($array, $length - 1);
        foreach ($withFirst as $combination) {
            $combinations[] = array_merge([$first], $combination);
        }

        $withoutFirst = $this->getCombinations($array, $length);
        $combinations = array_merge($combinations, $withoutFirst);

        return $combinations;
    }
}
