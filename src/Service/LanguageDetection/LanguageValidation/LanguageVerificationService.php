<?php

namespace App\Service\LanguageDetection\LanguageValidation;

use Elastica\Client;
use Elastica\Query;
use Elastica\Query\BoolQuery;
use Elastica\Query\Term;
use Elastica\Query\MatchQuery;
use App\Service\Search\ElasticsearchClientFactory;
use Psr\Log\LoggerInterface;

class LanguageVerificationService
{
    private const int TOP_WORDS_LIMIT = 2000;
    private const int MIN_WORD_LENGTH = 2;

    private Client $esClient;
    private string $indexName = 'words_index';

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
        $this->esClient = ElasticsearchClientFactory::create();
    }

    /**
     * Verify what percentage of text matches the target language using n-grams and fuzzy matching
     *
     * @param string $text Input text (can be obfuscated without spaces)
     * @param string $languageCode Target language code
     * @param int $minNgram Minimum n-gram length (default: 3)
     * @param int $maxNgram Maximum n-gram length (default: 5)
     * @param int $fuzziness Fuzzy matching fuzziness level (0-2, default: 1)
     * @return array Contains 'matchPercentage' and 'details'
     */
    public function verifyLanguage(
        string $text,
        string $languageCode,
        int $minNgram = self::DEFAULT_MIN_NGRAM,
        int $maxNgram = self::DEFAULT_MAX_NGRAM,
        int $fuzziness = 1
    ): array
    {
        if (empty($text) || empty($languageCode)) {
            return [
                'matchPercentage' => 0,
                'details' => [
                    'error' => 'Text and language code are required',
                    'textLength' => 0,
                    'matchedCharacters' => 0,
                ]
            ];
        }

        // Normalize input text
        $normalizedText = $this->normalizeText($text);
        $originalLength = mb_strlen($normalizedText);

        if ($originalLength === 0) {
            return [
                'matchPercentage' => 0,
                'details' => [
                    'textLength' => 0,
                    'matchedCharacters' => 0,
                    'ngramsGenerated' => 0,
                ]
            ];
        }

        // Fetch top words from the language
        $topWords = $this->fetchTopWords($languageCode);

        if (empty($topWords)) {
            $this->logger->warning("[LanguageVerificationService] No words found for language: {$languageCode}");
            return [
                'matchPercentage' => 0,
                'matchedWords' => [],
                'details' => [
                    'error' => 'No words found for language',
                    'textLength' => $originalLength,
                    'matchedCharacters' => 0,
                ]
            ];
        }

        // Find which words from dictionary appear in the text (with fuzzy matching)
        $matchedWords = [];
        $matchedPositions = []; // Track covered character positions

        foreach ($topWords as $word) {
            $normalizedWord = $this->normalizeText($word);

            if (mb_strlen($normalizedWord) < self::MIN_WORD_LENGTH) {
                continue;
            }

            // Try exact match first
            if ($this->findWordInText($normalizedText, $normalizedWord, $matchedPositions)) {
                $matchedWords[] = $word;
                continue;
            }

            // Try fuzzy match (allow 1-2 character differences)
            if ($fuzziness > 0 && $this->findWordFuzzy($normalizedText, $normalizedWord, $fuzziness, $matchedPositions)) {
                $matchedWords[] = $word;
            }
        }

        // Calculate percentage based on covered positions
        $matchedCharacters = count($matchedPositions);

        $matchPercentage = $originalLength > 0
            ? round(($matchedCharacters / $originalLength) * 100, 2)
            : 0;

        $result = [
            'matchPercentage' => $matchPercentage,
            'matchedWords' => $matchedWords,
            'details' => [
                'languageCode' => $languageCode,
                'textLength' => $originalLength,
                'matchedCharacters' => $matchedCharacters,
                'matchedWordsCount' => count($matchedWords),
                'topWordsChecked' => count($topWords),
                'fuzziness' => $fuzziness,
            ]
        ];

        $this->logger->info("[LanguageVerificationService] Language verification completed", $result);

        return $result;
    }

    /**
     * Fetch top N words with highest scores from Elasticsearch
     *
     * @param string $languageCode Target language code
     * @param int $limit Number of top words to fetch
     * @return array List of words sorted by score (descending)
     */
    private function fetchTopWords(string $languageCode, int $limit = self::TOP_WORDS_LIMIT): array
    {
        try {
            $boolQuery = new BoolQuery();

            $languageTerm = new Term();
            $languageTerm->setTerm('languageCode', $languageCode);
            $boolQuery->addFilter($languageTerm);

            $query = new Query($boolQuery);
            $query->setSize($limit);
            $query->setSort(['score' => ['order' => 'desc']]);

            $results = $this->esClient->getIndex($this->indexName)->search($query);

            return array_map(fn($r) => $r->getSource()['word'] ?? '', $results->getResults());
        } catch (\Exception $e) {
            $this->logger->error("[LanguageVerificationService] Error fetching top words: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Find exact word matches in text and mark covered positions
     *
     * @param string $text Normalized text to search in
     * @param string $word Normalized word to find
     * @param array $matchedPositions Reference to array tracking covered character positions
     * @return bool True if word was found
     */
    private function findWordInText(string $text, string $word, array &$matchedPositions): bool
    {
        $wordLength = mb_strlen($word);
        $found = false;
        $offset = 0;

        while (($pos = mb_strpos($text, $word, $offset)) !== false) {
            $found = true;

            // Mark all positions covered by this word
            for ($i = $pos; $i < $pos + $wordLength; $i++) {
                $matchedPositions[$i] = true;
            }

            $offset = $pos + 1;
        }

        return $found;
    }

    /**
     * Find fuzzy word matches using n-gram similarity
     *
     * @param string $text Normalized text to search in
     * @param string $word Normalized word to find
     * @param int $fuzziness Maximum edit distance
     * @param array $matchedPositions Reference to array tracking covered character positions
     * @return bool True if fuzzy match was found
     */
    private function findWordFuzzy(string $text, string $word, int $fuzziness, array &$matchedPositions): bool
    {
        $wordLength = mb_strlen($word);
        $textLength = mb_strlen($text);
        $found = false;

        // Scan through text with sliding window
        for ($i = 0; $i <= $textLength - $wordLength + $fuzziness; $i++) {
            for ($len = max($wordLength - $fuzziness, 1); $len <= $wordLength + $fuzziness; $len++) {
                if ($i + $len > $textLength) {
                    continue;
                }

                $substring = mb_substr($text, $i, $len);

                // Calculate Levenshtein distance
                if (levenshtein($substring, $word) <= $fuzziness) {
                    $found = true;

                    // Mark positions as covered
                    for ($j = $i; $j < $i + $len; $j++) {
                        $matchedPositions[$j] = true;
                    }

                    $i += $len - 1; // Skip ahead
                    break;
                }
            }
        }

        return $found;
    }

    /**
     * Normalize text by converting to lowercase and removing non-letter characters
     */
    private function normalizeText(string $text): string
    {
        $text = mb_strtolower($text);
        // Keep only letters and numbers, remove all other characters
        return preg_replace('/[^\p{L}\p{N}]/u', '', $text);
    }
}