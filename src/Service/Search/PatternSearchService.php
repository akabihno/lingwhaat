<?php

namespace App\Service\Search;

use App\Constant\ScriptAlphabets;
use App\Service\Cache\RedisCacheService;
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
        protected RedisCacheService $cache,
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
     * @param array|null $notLanguageCodes Optional array of language codes to exclude from results
     * @return array Array of matching words with their IPA and language code
     */
    public function findByAdvancedPattern(
        array $samePositions = [],
        array $fixedChars = [],
        ?int $exactLength = null,
        ?string $languageCode = null,
        int $limit = 100,
        ?array $notLanguageCodes = null
    ): array {
        if (empty($samePositions) && empty($fixedChars)) {
            return [];
        }

        try {
            // Build parameterized script to avoid script compilation limit
            $scriptData = $this->buildParameterizedScript($samePositions, $fixedChars, $exactLength);

            $script = new Script(
                $scriptData['source'],
                $scriptData['params'],
                'painless'
            );
            $scriptQuery = new Query\Script($script);

            $query = new Query\BoolQuery();
            $query->addFilter($scriptQuery);

            if ($languageCode !== null) {
                $languageQuery = new Query\Term();
                $languageQuery->setTerm('languageCode', $languageCode);
                $query->addMust($languageQuery);
            }

            if ($notLanguageCodes !== null && !empty($notLanguageCodes)) {
                foreach ($notLanguageCodes as $excludedLang) {
                    $excludeQuery = new Query\Term();
                    $excludeQuery->setTerm('languageCode', $excludedLang);
                    $query->addMustNot($excludeQuery);
                }
            }

            $mainQuery = new Query($query);
            $mainQuery->setSize($limit);

            $this->logger->info('Advanced pattern search initiated', [
                'service' => '[PatternSearchService]',
                'script_params' => $scriptData['params'],
                'same_positions' => $samePositions,
                'fixed_chars' => $fixedChars,
                'exact_length' => $exactLength,
                'languageCode' => $languageCode,
                'not_language_codes' => $notLanguageCodes,
                'limit' => $limit,
            ]);

            $results = $this->esClient->getIndex($this->indexName)->search($mainQuery);

            $result = array_map(function ($r) {
                return $r->getSource();
            }, $results->getResults());

            $this->logger->info('Advanced pattern search completed', [
                'service' => '[PatternSearchService]',
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
     * Builds a parameterized Painless script for positional pattern matching.
     * Uses parameters instead of inline values to avoid script compilation limit.
     *
     * @param array $samePositions Groups of positions that must contain the same character
     * @param array $fixedChars Fixed characters at specific positions
     * @param int|null $exactLength Optional exact word length
     * @return array Array with 'source' and 'params' keys
     */
    private function buildParameterizedScript(array $samePositions, array $fixedChars, ?int $exactLength = null): array
    {
        $params = [];

        // Prepare parameters
        $params['exactLength'] = $exactLength;
        $params['samePositions'] = [];
        $params['fixedChars'] = [];

        // Convert samePositions to 0-indexed and prepare for parameters
        foreach ($samePositions as $groupIndex => $group) {
            if (!empty($group) && count($group) >= 2) {
                $params['samePositions'][] = array_map(fn($p) => $p - 1, $group);
            }
        }

        // Convert fixedChars to 0-indexed and prepare for parameters
        foreach ($fixedChars as $position => $char) {
            if ($position >= 1) {
                $params['fixedChars'][] = [
                    'pos' => $position - 1,
                    'char' => strtolower($char)
                ];
            }
        }

        // Calculate all constrained positions
        $allConstrainedPositions = [];
        foreach ($samePositions as $group) {
            foreach ($group as $pos) {
                $allConstrainedPositions[] = $pos - 1;
            }
        }
        foreach ($fixedChars as $position => $char) {
            if ($position >= 1) {
                $allConstrainedPositions[] = $position - 1;
            }
        }
        $params['constrainedPositions'] = array_values(array_unique($allConstrainedPositions));

        // Calculate max position for length check
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
        $params['maxPosition'] = $maxPosition;

        // Build generic parameterized script
        $script = "
            if (doc['word.keyword'].size() == 0) { return false; }
            String word = doc['word.keyword'].value.toLowerCase();

            // Check exact length if specified
            if (params.exactLength != null && word.length() != params.exactLength) {
                return false;
            }

            // Check minimum length
            if (params.maxPosition > 0 && word.length() < params.maxPosition) {
                return false;
            }

            // Check same position constraints
            for (int groupIdx = 0; groupIdx < params.samePositions.size(); groupIdx++) {
                def group = params.samePositions[groupIdx];
                if (group.size() < 2) continue;

                int firstPos = group[0];
                char firstChar = word.charAt(firstPos);

                // Check all positions in group have same character
                for (int i = 1; i < group.size(); i++) {
                    if (word.charAt(group[i]) != firstChar) {
                        return false;
                    }
                }

                // Check character appears ONLY at these positions
                for (int idx = 0; idx < word.length(); idx++) {
                    if (word.charAt(idx) == firstChar) {
                        boolean found = false;
                        for (int allowedPos : group) {
                            if (idx == allowedPos) {
                                found = true;
                                break;
                            }
                        }
                        if (!found) return false;
                    }
                }
            }

            // Check fixed character constraints
            for (int i = 0; i < params.fixedChars.size(); i++) {
                def constraint = params.fixedChars[i];
                int pos = constraint.pos;
                String charStr = constraint['char'];
                char expectedChar = charStr.charAt(0);

                if (word.charAt(pos) != expectedChar) {
                    return false;
                }

                // Ensure this character appears ONLY at this position
                for (int idx = 0; idx < word.length(); idx++) {
                    if (idx != pos && word.charAt(idx) == expectedChar) {
                        return false;
                    }
                }
            }

            // Check that repeated characters are fully constrained
            if (params.constrainedPositions.size() > 0) {
                for (int i = 0; i < word.length(); i++) {
                    char currentChar = word.charAt(i);
                    int count = 0;
                    for (int j = 0; j < word.length(); j++) {
                        if (word.charAt(j) == currentChar) {
                            count++;
                        }
                    }
                    if (count > 1) {
                        boolean isConstrained = false;
                        for (int cp : params.constrainedPositions) {
                            if (i == cp) {
                                isConstrained = true;
                                break;
                            }
                        }
                        if (!isConstrained) {
                            return false;
                        }
                    }
                }
            }

            return true;
        ";

        return [
            'source' => $script,
            'params' => $params
        ];
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

    /**
     * Search for word sequences where multiple letters appear exclusively at given positions across words.
     * This is useful for solving substitution ciphers where you know multiple letter constraints.
     *
     * @param array $letterConstraints Array of position arrays, each representing constraints for one letter
     *                                 Use empty array [] to indicate no constraint for that word.
     *                                 Example: [[[1,4], [3], []], [[2], [1,2], [5]]] means:
     *                                 - First letter at positions 1,4 in word 1, position 3 in word 2, no constraint in word 3
     *                                 - Second letter at position 2 in word 1, positions 1,2 in word 2, position 5 in word 3
     * @param array|null $exactLengths Optional array of exact lengths for each word in the sequence
     * @param string|null $languageCode Optional language filter
     * @param int $limit Maximum number of result groups to return
     * @param array|null $notLanguageCodes Optional array of language codes to exclude from results
     * @return array Array of results grouped by language code, ordered alphabetically
     */
    public function findByMultiLetterSequencePattern(
        array $letterConstraints,
        ?array $exactLengths = null,
        ?string $languageCode = null,
        int $limit = 100,
        ?array $notLanguageCodes = null
    ): array {
        if (empty($letterConstraints)) {
            return [];
        }

        try {
            $this->logger->info('Multi-letter sequence pattern search initiated', [
                'service' => '[PatternSearchService]',
                'letter_constraints' => $letterConstraints,
                'exact_lengths' => $exactLengths,
                'languageCode' => $languageCode,
                'not_language_codes' => $notLanguageCodes,
                'limit' => $limit,
            ]);

            $allResults = [];

            // Get the appropriate alphabet for the language
            // languageCode is required for multi-letter searches (enforced in controller)
            $alphabet = ScriptAlphabets::getAlphabetForLanguage($languageCode);

            // OPTIMIZATION: Pre-filter viable letters to reduce search space
            // Instead of trying all 26^N combinations, find which letters actually have matches
            $viableLettersPerConstraint = $this->findViableLetters(
                $letterConstraints,
                $exactLengths,
                $languageCode,
                $notLanguageCodes,
                $alphabet
            );

            // If any constraint has no viable letters, return early
            foreach ($viableLettersPerConstraint as $viableLetters) {
                if (empty($viableLetters)) {
                    $this->logger->info('No viable letters found for constraint', [
                        'service' => '[PatternSearchService]',
                    ]);
                    return [];
                }
            }

            $this->logger->info('Viable letters found', [
                'service' => '[PatternSearchService]',
                'viable_counts' => array_map('count', $viableLettersPerConstraint),
            ]);

            // Generate letter assignments using only viable letters
            $this->findLetterAssignmentsOptimized(
                $letterConstraints,
                $exactLengths,
                $languageCode,
                $notLanguageCodes,
                $viableLettersPerConstraint,
                [],
                0,
                $allResults,
                $limit
            );

            // Group by language code and order
            $groupedResults = [];
            foreach ($allResults as $result) {
                $langCode = $result['languageCode'];
                if (!isset($groupedResults[$langCode])) {
                    $groupedResults[$langCode] = [];
                }
                $groupedResults[$langCode][] = $result;
            }

            // Sort by language code
            ksort($groupedResults);

            // Convert to desired format
            $finalResults = [];
            foreach ($groupedResults as $langCode => $sequences) {
                $finalResults[] = [
                    'languageCode' => $langCode,
                    'sequences' => array_slice($sequences, 0, $limit)
                ];
            }

            $this->logger->info('Multi-letter sequence pattern search completed', [
                'service' => '[PatternSearchService]',
                'result_count' => count($finalResults),
            ]);

            return $finalResults;
        } catch (Exception $e) {
            $this->logger->error('Multi-letter sequence pattern search failed', [
                'service' => '[PatternSearchService]',
                'letter_constraints' => $letterConstraints,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Find which letters are actually viable for each constraint by doing quick test queries.
     * This dramatically reduces the search space.
     * Uses parallel _msearch and Redis caching for optimal performance.
     *
     * @param array $letterConstraints All letter constraints
     * @param array|null $exactLengths Exact lengths for words
     * @param string $languageCode Language code
     * @param array|null $notLanguageCodes Language codes to exclude
     * @param string $alphabet Alphabet to test
     * @return array Array of arrays, each containing viable letters for a constraint
     */
    private function findViableLetters(
        array $letterConstraints,
        ?array $exactLengths,
        string $languageCode,
        ?array $notLanguageCodes,
        string $alphabet
    ): array {
        // Check cache first
        $cacheKey = $this->cache->generateKey('viable_letters', [
            'constraints' => $letterConstraints,
            'lengths' => $exactLengths,
            'lang' => $languageCode,
            'not_langs' => $notLanguageCodes,
            'alphabet' => $alphabet
        ]);

        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            $this->logger->info('Viable letters cache hit', [
                'service' => '[PatternSearchService]',
            ]);
            return $cached;
        }

        $viableLettersPerConstraint = [];

        foreach ($letterConstraints as $constraintIndex => $constraint) {
            $viableLetters = [];

            // Build all queries for parallel execution
            $msearchQueries = [];
            $letterMap = []; // Map search index to letter

            for ($i = 0; $i < mb_strlen($alphabet, 'UTF-8'); $i++) {
                $letter = mb_substr($alphabet, $i, 1, 'UTF-8');

                // Check each word position for this letter
                foreach ($constraint as $wordIndex => $positions) {
                    if (empty($positions)) {
                        continue;
                    }

                    // Build query for this letter at these positions
                    $fixedChars = [];
                    foreach ($positions as $pos) {
                        $fixedChars[$pos] = $letter;
                    }
                    $samePositions = [array_values($positions)];
                    $exactLength = isset($exactLengths[$wordIndex]) ? $exactLengths[$wordIndex] : null;

                    // Build the query
                    $query = $this->buildAdvancedPatternQuery(
                        $samePositions,
                        $fixedChars,
                        $exactLength,
                        $languageCode,
                        $notLanguageCodes
                    );

                    $msearchQueries[] = ['index' => $this->indexName];
                    $msearchQueries[] = array_merge($query->toArray(), ['size' => 1]);
                    $letterMap[] = ['letter' => $letter, 'wordIndex' => $wordIndex];

                    // Only need to test one word position per letter
                    break;
                }
            }

            // Execute all queries in parallel using _msearch
            if (!empty($msearchQueries)) {
                $viableLetters = $this->executeMsearchForViableLetters($msearchQueries, $letterMap);
            }

            $viableLettersPerConstraint[$constraintIndex] = $viableLetters;
        }

        // Cache the results for 1 hour
        $this->cache->set($cacheKey, $viableLettersPerConstraint, 3600);

        return $viableLettersPerConstraint;
    }

    /**
     * Execute msearch query and extract viable letters from results.
     *
     * @param array $msearchQueries Array of msearch query pairs
     * @param array $letterMap Mapping of result indices to letters
     * @return array Array of viable letters
     */
    private function executeMsearchForViableLetters(array $msearchQueries, array $letterMap): array
    {
        try {
            $response = $this->esClient->request('_msearch', 'POST', $msearchQueries);
            $responses = $response->getData()['responses'] ?? [];

            $viableLetters = [];
            foreach ($responses as $index => $resp) {
                if (isset($resp['hits']['total']['value']) && $resp['hits']['total']['value'] > 0) {
                    $letter = $letterMap[$index]['letter'];
                    if (!in_array($letter, $viableLetters)) {
                        $viableLetters[] = $letter;
                    }
                }
            }

            return $viableLetters;
        } catch (Exception $e) {
            $this->logger->error('Msearch for viable letters failed', [
                'service' => '[PatternSearchService]',
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Build Elasticsearch query for advanced pattern matching.
     *
     * @param array $samePositions Position groups
     * @param array $fixedChars Fixed characters
     * @param int|null $exactLength Exact length
     * @param string|null $languageCode Language filter
     * @param array|null $notLanguageCodes Languages to exclude
     * @return Query
     */
    private function buildAdvancedPatternQuery(
        array $samePositions,
        array $fixedChars,
        ?int $exactLength,
        ?string $languageCode,
        ?array $notLanguageCodes
    ): Query {
        $scriptData = $this->buildParameterizedScript($samePositions, $fixedChars, $exactLength);

        $script = new Script(
            $scriptData['source'],
            $scriptData['params'],
            'painless'
        );
        $scriptQuery = new Query\Script($script);

        $query = new Query\BoolQuery();
        $query->addFilter($scriptQuery);

        if ($languageCode !== null) {
            $languageQuery = new Query\Term();
            $languageQuery->setTerm('languageCode', $languageCode);
            $query->addMust($languageQuery);
        }

        if ($notLanguageCodes !== null && !empty($notLanguageCodes)) {
            foreach ($notLanguageCodes as $excludedLang) {
                $excludeQuery = new Query\Term();
                $excludeQuery->setTerm('languageCode', $excludedLang);
                $query->addMustNot($excludeQuery);
            }
        }

        return new Query($query);
    }

    /**
     * Optimized version that uses pre-filtered viable letters.
     *
     * @param array $letterConstraints All letter constraints
     * @param array|null $exactLengths Exact lengths for words
     * @param string|null $languageCode Language filter
     * @param array|null $notLanguageCodes Language codes to exclude
     * @param array $viableLettersPerConstraint Pre-filtered viable letters for each constraint
     * @param array $assignedLetters Already assigned letters
     * @param int $currentIndex Current constraint index
     * @param array &$results Results array
     * @param int $limit Result limit
     */
    private function findLetterAssignmentsOptimized(
        array $letterConstraints,
        ?array $exactLengths,
        ?string $languageCode,
        ?array $notLanguageCodes,
        array $viableLettersPerConstraint,
        array $assignedLetters,
        int $currentIndex,
        array &$results,
        int $limit
    ): void {
        if (count($results) >= $limit) {
            return;
        }

        if ($currentIndex >= count($letterConstraints)) {
            // All letters assigned, now find matching word sequences
            $this->findSequenceWithLetterAssignments(
                $assignedLetters,
                $letterConstraints,
                $exactLengths,
                $languageCode,
                $notLanguageCodes,
                $results,
                $limit
            );
            return;
        }

        // Try only viable letters for this constraint
        $viableLetters = $viableLettersPerConstraint[$currentIndex];

        foreach ($viableLetters as $letter) {
            // Skip if letter already assigned
            if (in_array($letter, $assignedLetters)) {
                continue;
            }

            $newAssignedLetters = $assignedLetters;
            $newAssignedLetters[] = $letter;

            $this->findLetterAssignmentsOptimized(
                $letterConstraints,
                $exactLengths,
                $languageCode,
                $notLanguageCodes,
                $viableLettersPerConstraint,
                $newAssignedLetters,
                $currentIndex + 1,
                $results,
                $limit
            );

            if (count($results) >= $limit) {
                return;
            }
        }
    }

    /**
     * Recursively find valid letter assignments for multi-letter constraints.
     *
     * @param array $letterConstraints All letter constraints
     * @param array|null $exactLengths Exact lengths for words
     * @param string|null $languageCode Language filter
     * @param array|null $notLanguageCodes Language codes to exclude
     * @param string $alphabet Available letters
     * @param array $assignedLetters Already assigned letters
     * @param int $currentIndex Current constraint index
     * @param array &$results Results array
     * @param int $limit Result limit
     */
    private function findLetterAssignments(
        array $letterConstraints,
        ?array $exactLengths,
        ?string $languageCode,
        ?array $notLanguageCodes,
        string $alphabet,
        array $assignedLetters,
        int $currentIndex,
        array &$results,
        int $limit
    ): void {
        if (count($results) >= $limit) {
            return;
        }

        if ($currentIndex >= count($letterConstraints)) {
            // All letters assigned, now find matching word sequences
            $this->findSequenceWithLetterAssignments(
                $assignedLetters,
                $letterConstraints,
                $exactLengths,
                $languageCode,
                $notLanguageCodes,
                $results,
                $limit
            );
            return;
        }

        // Try each available letter for this constraint
        // Use mb_strlen/mb_substr for proper UTF-8 handling (non-Latin scripts)
        for ($i = 0; $i < mb_strlen($alphabet, 'UTF-8'); $i++) {
            $letter = mb_substr($alphabet, $i, 1, 'UTF-8');

            // Skip if letter already assigned
            if (in_array($letter, $assignedLetters)) {
                continue;
            }

            $newAssignedLetters = $assignedLetters;
            $newAssignedLetters[] = $letter;

            $this->findLetterAssignments(
                $letterConstraints,
                $exactLengths,
                $languageCode,
                $notLanguageCodes,
                $alphabet,
                $newAssignedLetters,
                $currentIndex + 1,
                $results,
                $limit
            );

            if (count($results) >= $limit) {
                return;
            }
        }
    }

    /**
     * Find word sequences that match all assigned letter constraints.
     * Uses parallel _msearch for optimal performance.
     *
     * @param array $assignedLetters Array of letters assigned to each constraint
     * @param array $letterConstraints Original letter constraints
     * @param array|null $exactLengths Exact lengths for words
     * @param string|null $languageCode Language filter
     * @param array|null $notLanguageCodes Language codes to exclude
     * @param array &$results Results array
     * @param int $limit Result limit
     */
    private function findSequenceWithLetterAssignments(
        array $assignedLetters,
        array $letterConstraints,
        ?array $exactLengths,
        ?string $languageCode,
        ?array $notLanguageCodes,
        array &$results,
        int $limit
    ): void {
        if (count($results) >= $limit) {
            return;
        }

        // Determine number of words in sequence from first constraint
        $numWords = count($letterConstraints[0]);

        // Build all queries for parallel execution
        $msearchQueries = [];
        $wordQueryMap = [];

        for ($wordIndex = 0; $wordIndex < $numWords; $wordIndex++) {
            $samePositions = [];
            $fixedChars = [];

            // Collect constraints from all letters for this word
            foreach ($letterConstraints as $constraintIndex => $constraint) {
                if (!isset($constraint[$wordIndex]) || empty($constraint[$wordIndex])) {
                    continue;
                }

                $positions = $constraint[$wordIndex];
                $letter = $assignedLetters[$constraintIndex];

                // Add to samePositions
                if (count($positions) > 0) {
                    $samePositions[] = array_values($positions);

                    // Add to fixedChars
                    foreach ($positions as $pos) {
                        $fixedChars[$pos] = $letter;
                    }
                }
            }

            // Get exact length if specified for this word
            $exactLength = isset($exactLengths[$wordIndex]) ? $exactLengths[$wordIndex] : null;

            // Build query
            $query = $this->buildAdvancedPatternQuery(
                $samePositions,
                $fixedChars,
                $exactLength,
                $languageCode,
                $notLanguageCodes
            );

            $msearchQueries[] = ['index' => $this->indexName];
            $msearchQueries[] = array_merge($query->toArray(), ['size' => 50]);
            $wordQueryMap[] = $wordIndex;
        }

        // Execute all queries in parallel
        $wordResultsByPosition = $this->executeMsearchForWordPositions($msearchQueries, $wordQueryMap);

        // Check if all word positions have results
        for ($wordIndex = 0; $wordIndex < $numWords; $wordIndex++) {
            if (empty($wordResultsByPosition[$wordIndex])) {
                // No words found for this position, can't form sequence
                return;
            }
        }

        // Combine words to form sequences
        $sequences = $this->combineMultiLetterSequenceWords($wordResultsByPosition, $assignedLetters, $letterConstraints, $limit);

        foreach ($sequences as $sequence) {
            if (count($results) >= $limit) {
                return;
            }
            $results[] = $sequence;
        }
    }

    /**
     * Execute msearch query and extract word results for each position.
     *
     * @param array $msearchQueries Array of msearch query pairs
     * @param array $wordQueryMap Mapping of result indices to word positions
     * @return array Array of word results by position
     */
    private function executeMsearchForWordPositions(array $msearchQueries, array $wordQueryMap): array
    {
        try {
            $response = $this->esClient->request('_msearch', 'POST', $msearchQueries);
            $responses = $response->getData()['responses'] ?? [];

            $wordResultsByPosition = [];
            foreach ($responses as $index => $resp) {
                $wordIndex = $wordQueryMap[$index];
                $hits = $resp['hits']['hits'] ?? [];

                $wordResultsByPosition[$wordIndex] = array_map(function ($hit) {
                    return $hit['_source'];
                }, $hits);
            }

            return $wordResultsByPosition;
        } catch (Exception $e) {
            $this->logger->error('Msearch for word positions failed', [
                'service' => '[PatternSearchService]',
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Combine words from different positions to form complete multi-letter sequences.
     *
     * @param array $wordResultsByPosition Words for each position in the sequence
     * @param array $assignedLetters Letters assigned to constraints
     * @param array $letterConstraints Original constraints
     * @param int $limit Maximum number of sequences to return
     * @return array Array of combined sequences
     */
    private function combineMultiLetterSequenceWords(
        array $wordResultsByPosition,
        array $assignedLetters,
        array $letterConstraints,
        int $limit
    ): array {
        if (empty($wordResultsByPosition)) {
            return [];
        }

        $sequences = [];
        $positionIndices = array_keys($wordResultsByPosition);

        // Start with the first position
        $firstPosition = $positionIndices[0];

        foreach ($wordResultsByPosition[$firstPosition] as $firstWord) {
            $this->recursiveMultiLetterSequenceBuild(
                $wordResultsByPosition,
                $positionIndices,
                1,
                [$firstWord],
                $sequences,
                $assignedLetters,
                $letterConstraints,
                $limit
            );

            if (count($sequences) >= $limit) {
                break;
            }
        }

        return array_slice($sequences, 0, $limit);
    }

    /**
     * Recursively build multi-letter word sequences.
     *
     * @param array $wordResultsByPosition Words for each position
     * @param array $positionIndices Position indices
     * @param int $currentIndex Current position in the sequence
     * @param array $currentSequence Current sequence being built
     * @param array &$sequences Reference to store completed sequences
     * @param array $assignedLetters Letters assigned to constraints
     * @param array $letterConstraints Original constraints
     * @param int $limit Maximum sequences
     */
    private function recursiveMultiLetterSequenceBuild(
        array $wordResultsByPosition,
        array $positionIndices,
        int $currentIndex,
        array $currentSequence,
        array &$sequences,
        array $assignedLetters,
        array $letterConstraints,
        int $limit
    ): void {
        if (count($sequences) >= $limit) {
            return;
        }

        if ($currentIndex >= count($positionIndices)) {
            // Check if all words are from the same language
            $languageCode = $currentSequence[0]['languageCode'];
            $allSameLanguage = true;
            foreach ($currentSequence as $word) {
                if ($word['languageCode'] !== $languageCode) {
                    $allSameLanguage = false;
                    break;
                }
            }

            if ($allSameLanguage) {
                $sequences[] = [
                    'languageCode' => $languageCode,
                    'letters' => $assignedLetters,
                    'words' => array_map(fn($w) => [
                        'word' => $w['word'],
                        'ipa' => $w['ipa'] ?? null
                    ], $currentSequence)
                ];
            }
            return;
        }

        $positionIndex = $positionIndices[$currentIndex];

        if (!isset($wordResultsByPosition[$positionIndex])) {
            return;
        }

        foreach ($wordResultsByPosition[$positionIndex] as $word) {
            $newSequence = $currentSequence;
            $newSequence[] = $word;

            $this->recursiveMultiLetterSequenceBuild(
                $wordResultsByPosition,
                $positionIndices,
                $currentIndex + 1,
                $newSequence,
                $sequences,
                $assignedLetters,
                $letterConstraints,
                $limit
            );

            if (count($sequences) >= $limit) {
                break;
            }
        }
    }

    /**
     * Search for word sequences where a specific letter appears exclusively at given positions across words.
     *
     * @param array $sequencePositions Array of position arrays for each word in the sequence
     *                                 Example: [[1,4], [3], [9]] means letter at positions 1,4 in word 1, position 3 in word 2, position 9 in word 3
     * @param array|null $exactLengths Optional array of exact lengths for each word in the sequence
     *                                 Example: [4, 3, 9] means word 1 must be exactly 4 chars, word 2 must be 3 chars, word 3 must be 9 chars
     * @param string|null $languageCode Optional language filter
     * @param int $limit Maximum number of result groups to return
     * @param array|null $notLanguageCodes Optional array of language codes to exclude from results
     * @return array Array of results grouped by language code, ordered alphabetically
     */
    public function findBySequencePattern(
        array $sequencePositions,
        ?array $exactLengths = null,
        ?string $languageCode = null,
        int $limit = 100,
        ?array $notLanguageCodes = null
    ): array {
        if (empty($sequencePositions)) {
            return [];
        }

        try {
            $this->logger->info('Sequence pattern search initiated', [
                'service' => '[PatternSearchService]',
                'sequence_positions' => $sequencePositions,
                'exact_lengths' => $exactLengths,
                'languageCode' => $languageCode,
                'not_language_codes' => $notLanguageCodes,
                'limit' => $limit,
            ]);

            $allResults = [];

            // Get the appropriate alphabet for the language
            // Use Latin alphabet as fallback if no language specified
            $alphabet = $languageCode !== null
                ? ScriptAlphabets::getAlphabetForLanguage($languageCode)
                : ScriptAlphabets::LATIN_ALPHABET;

            // Try each letter of the alphabet
            for ($i = 0; $i < mb_strlen($alphabet, 'UTF-8'); $i++) {
                $letter = mb_substr($alphabet, $i, 1, 'UTF-8');

                $sequenceWords = $this->findSequenceForLetter(
                    $letter,
                    $sequencePositions,
                    $exactLengths,
                    $languageCode,
                    $notLanguageCodes,
                    $limit
                );

                if (!empty($sequenceWords)) {
                    foreach ($sequenceWords as $result) {
                        $allResults[] = $result;
                    }
                }

                // Stop if we have enough results
                if (count($allResults) >= $limit) {
                    break;
                }
            }

            // Group by language code and order
            $groupedResults = [];
            foreach ($allResults as $result) {
                $langCode = $result['languageCode'];
                if (!isset($groupedResults[$langCode])) {
                    $groupedResults[$langCode] = [];
                }
                $groupedResults[$langCode][] = $result;
            }

            // Sort by language code
            ksort($groupedResults);

            // Convert to desired format
            $finalResults = [];
            foreach ($groupedResults as $langCode => $words) {
                $finalResults[] = [
                    'languageCode' => $langCode,
                    'sequences' => array_slice($words, 0, $limit)
                ];
            }

            $this->logger->info('Sequence pattern search completed', [
                'service' => '[PatternSearchService]',
                'result_count' => count($finalResults),
            ]);

            return $finalResults;
        } catch (Exception $e) {
            $this->logger->error('Sequence pattern search failed', [
                'service' => '[PatternSearchService]',
                'sequence_positions' => $sequencePositions,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Find word sequences for a specific letter at given positions.
     * Uses parallel _msearch and Redis caching for optimal performance.
     *
     * @param string $letter The letter to search for
     * @param array $sequencePositions Position arrays for each word
     * @param array|null $exactLengths Optional exact lengths for each word
     * @param string|null $languageCode Optional language filter
     * @param array|null $notLanguageCodes Language codes to exclude
     * @param int $limit Maximum results per letter
     * @return array Array of matching sequences
     */
    private function findSequenceForLetter(
        string $letter,
        array $sequencePositions,
        ?array $exactLengths,
        ?string $languageCode,
        ?array $notLanguageCodes,
        int $limit
    ): array {
        // Check cache first
        $cacheKey = $this->cache->generateKey('sequence_letter', [
            'letter' => $letter,
            'positions' => $sequencePositions,
            'lengths' => $exactLengths,
            'lang' => $languageCode,
            'not_langs' => $notLanguageCodes,
            'limit' => $limit
        ]);

        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // Build all queries for parallel execution
        $msearchQueries = [];
        $wordQueryMap = [];

        foreach ($sequencePositions as $wordIndex => $positions) {
            if (empty($positions)) {
                continue;
            }

            // Build fixedChars for this pattern
            $fixedChars = [];
            foreach ($positions as $pos) {
                $fixedChars[$pos] = $letter;
            }

            // Use samePositions to ensure the letter appears at all specified positions
            $samePositions = [array_values($positions)];

            // Get exact length if specified for this word
            $exactLength = isset($exactLengths[$wordIndex]) ? $exactLengths[$wordIndex] : null;

            // Build query
            $query = $this->buildAdvancedPatternQuery(
                $samePositions,
                $fixedChars,
                $exactLength,
                $languageCode,
                $notLanguageCodes
            );

            $msearchQueries[] = ['index' => $this->indexName];
            $msearchQueries[] = array_merge($query->toArray(), ['size' => 50]);
            $wordQueryMap[] = $wordIndex;
        }

        // Execute all queries in parallel
        $wordResultsByPosition = [];
        if (!empty($msearchQueries)) {
            $wordResultsByPosition = $this->executeMsearchForWordPositions($msearchQueries, $wordQueryMap);
        }

        // Combine words from each position to form sequences
        $sequences = $this->combineSequenceWords($wordResultsByPosition, $letter, $limit);

        // Cache the results for 30 minutes
        $this->cache->set($cacheKey, $sequences, 1800);

        return $sequences;
    }

    /**
     * Combine words from different positions to form complete sequences.
     *
     * @param array $wordResultsByPosition Words for each position in the sequence
     * @param string $letter The letter being searched
     * @param int $limit Maximum number of sequences to return
     * @return array Array of combined sequences
     */
    private function combineSequenceWords(array $wordResultsByPosition, string $letter, int $limit): array
    {
        if (empty($wordResultsByPosition)) {
            return [];
        }

        $sequences = [];
        $positionIndices = array_keys($wordResultsByPosition);

        // Start with the first position
        $firstPosition = $positionIndices[0];

        foreach ($wordResultsByPosition[$firstPosition] as $firstWord) {
            $this->recursiveSequenceBuild(
                $wordResultsByPosition,
                $positionIndices,
                1,
                [$firstWord],
                $sequences,
                $letter,
                $limit
            );

            if (count($sequences) >= $limit) {
                break;
            }
        }

        return array_slice($sequences, 0, $limit);
    }

    /**
     * Recursively build word sequences.
     *
     * @param array $wordResultsByPosition Words for each position
     * @param array $positionIndices Position indices
     * @param int $currentIndex Current position in the sequence
     * @param array $currentSequence Current sequence being built
     * @param array &$sequences Reference to store completed sequences
     * @param string $letter The letter being searched
     * @param int $limit Maximum sequences
     */
    private function recursiveSequenceBuild(
        array $wordResultsByPosition,
        array $positionIndices,
        int $currentIndex,
        array $currentSequence,
        array &$sequences,
        string $letter,
        int $limit
    ): void {
        if (count($sequences) >= $limit) {
            return;
        }

        if ($currentIndex >= count($positionIndices)) {
            // Check if all words are from the same language
            $languageCode = $currentSequence[0]['languageCode'];
            $allSameLanguage = true;
            foreach ($currentSequence as $word) {
                if ($word['languageCode'] !== $languageCode) {
                    $allSameLanguage = false;
                    break;
                }
            }

            if ($allSameLanguage) {
                $sequences[] = [
                    'languageCode' => $languageCode,
                    'letter' => $letter,
                    'words' => array_map(fn($w) => [
                        'word' => $w['word'],
                        'ipa' => $w['ipa'] ?? null
                    ], $currentSequence)
                ];
            }
            return;
        }

        $positionIndex = $positionIndices[$currentIndex];

        if (!isset($wordResultsByPosition[$positionIndex])) {
            return;
        }

        foreach ($wordResultsByPosition[$positionIndex] as $word) {
            $newSequence = $currentSequence;
            $newSequence[] = $word;

            $this->recursiveSequenceBuild(
                $wordResultsByPosition,
                $positionIndices,
                $currentIndex + 1,
                $newSequence,
                $sequences,
                $letter,
                $limit
            );

            if (count($sequences) >= $limit) {
                break;
            }
        }
    }
}
