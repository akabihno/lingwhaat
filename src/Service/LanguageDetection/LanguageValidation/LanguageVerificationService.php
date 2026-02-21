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
    private const int DEFAULT_MIN_NGRAM = 3;
    private const int DEFAULT_MAX_NGRAM = 5;

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

        // Generate n-grams from text
        $ngrams = $this->generateNgrams($normalizedText, $minNgram, $maxNgram);

        if (empty($ngrams)) {
            return [
                'matchPercentage' => 0,
                'details' => [
                    'textLength' => $originalLength,
                    'matchedCharacters' => 0,
                    'ngramsGenerated' => 0,
                ]
            ];
        }

        // Search for n-gram matches in Elasticsearch
        $matchResults = $this->searchNgramsWithFuzzy($ngrams, $languageCode, $fuzziness);

        // Calculate coverage and collect matched words
        $matchedCharacters = 0;
        $uniqueMatches = [];
        $matchedWordsSet = [];

        foreach ($matchResults as $ngram => $matches) {
            if (!empty($matches)) {
                $matchedCharacters += mb_strlen($ngram);
                $uniqueMatches[$ngram] = $matches;

                // Collect all matched words
                foreach ($matches as $word) {
                    $matchedWordsSet[$word] = true;
                }
            }
        }

        $matchedWords = array_keys($matchedWordsSet);

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
                'ngramsGenerated' => count($ngrams),
                'ngramsMatched' => count($uniqueMatches),
                'matchedWordsCount' => count($matchedWords),
                'minNgram' => $minNgram,
                'maxNgram' => $maxNgram,
                'fuzziness' => $fuzziness,
            ]
        ];

        $this->logger->info("[LanguageVerificationService] Language verification completed", $result);

        return $result;
    }

    /**
     * Generate n-grams from text
     *
     * @param string $text Normalized text
     * @param int $minLength Minimum n-gram length
     * @param int $maxLength Maximum n-gram length
     * @return array Unique n-grams
     */
    private function generateNgrams(string $text, int $minLength, int $maxLength): array
    {
        $ngrams = [];
        $textLength = mb_strlen($text);

        for ($length = $minLength; $length <= $maxLength; $length++) {
            for ($i = 0; $i <= $textLength - $length; $i++) {
                $ngram = mb_substr($text, $i, $length);
                if (mb_strlen($ngram) === $length) {
                    $ngrams[$ngram] = true; // Use array key for deduplication
                }
            }
        }

        return array_keys($ngrams);
    }

    /**
     * Search n-grams in Elasticsearch with fuzzy matching
     *
     * @param array $ngrams List of n-grams to search
     * @param string $languageCode Target language code
     * @param int $fuzziness Fuzziness level (0-2)
     * @return array Map of ngram => matched words
     */
    private function searchNgramsWithFuzzy(array $ngrams, string $languageCode, int $fuzziness): array
    {
        try {
            $results = [];

            // Batch n-grams to avoid too many queries
            $batchSize = 50;
            $ngramBatches = array_chunk($ngrams, $batchSize);

            foreach ($ngramBatches as $batch) {
                foreach ($batch as $ngram) {
                    $boolQuery = new BoolQuery();

                    // Use n-gram field with fuzzy matching
                    $matchQuery = new MatchQuery();
                    $matchQuery->setFieldQuery('word.ngram', $ngram);
                    $matchQuery->setFieldFuzziness('word.ngram', $fuzziness);

                    $boolQuery->addShould($matchQuery);

                    // Filter by language
                    $languageTerm = new Term();
                    $languageTerm->setTerm('languageCode', $languageCode);
                    $boolQuery->addFilter($languageTerm);

                    $query = new Query($boolQuery);
                    $query->setSize(5); // Limit results per n-gram
                    $query->setSort(['score' => ['order' => 'desc']]);

                    $searchResults = $this->esClient->getIndex($this->indexName)->search($query);

                    if ($searchResults->count() > 0) {
                        $results[$ngram] = array_map(
                            fn($r) => $r->getSource()['word'] ?? '',
                            $searchResults->getResults()
                        );
                    }
                }
            }

            return $results;
        } catch (\Exception $e) {
            $this->logger->error("[LanguageVerificationService] Error searching n-grams: {$e->getMessage()}");
            return [];
        }
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