<?php

namespace App\Service\Search;

use Elastica\Client;
use Elastica\Query;
use Elastica\Query\BoolQuery;
use Elastica\Query\Term;
use App\Service\LanguageNormalizationService;

class TextDecryptionService
{
    private Client $esClient;
    private string $indexName = 'words_index';
    private LanguageNormalizationService $normalizationService;

    private array $substitutionPatterns = [
        ['d' => 'g', 'g' => 'd'],
        ['d' => 't', 't' => 'd'],
        ['c' => 'k', 'k' => 'c'],
        ['e' => 'a', 'a' => 'e'],
        ['e' => 'i', 'i' => 'e'],
        ['o' => 'u', 'u' => 'o'],
        ['v' => 'f', 'f' => 'v'],
        ['s' => 'z', 'z' => 's'],
    ];

    private array $questionMarkReplacements = ['a', 'e', 'i', 'o', 'u', 'c', 'd', 'g', 'h', 'k', 'l', 'm', 'n', 'r', 's', 't', 'v', 'w'];

    public function __construct()
    {
        $this->esClient = ElasticsearchClientFactory::create();
        $this->normalizationService = new LanguageNormalizationService();
    }

    /**
     * Attempts to decrypt text by trying different letter substitutions and question mark replacements
     *
     * @param string $text The encrypted text
     * @param string $languageCode The target language code (e.g., 'odt' for Old Dutch)
     * @param int $minCount Minimum number of words that must match for a valid result
     * @return array Result containing 'success', 'original_text', 'decrypted_text', 'match_count', 'matched_words', and 'substitutions'
     */
    public function decryptText(string $text, string $languageCode, int $minCount = 5): array
    {
        $normalizedText = $this->normalizationService->normalizeText($text);
        $words = preg_split('/\s+/', $normalizedText);
        $words = array_filter($words, fn($w) => !empty($w));

        $bestResult = [
            'success' => false,
            'original_text' => $text,
            'decrypted_text' => null,
            'match_count' => 0,
            'matched_words' => [],
            'substitutions' => [],
        ];

        $result = $this->tryDecryption($words, $languageCode, []);
        if ($result['match_count'] > $bestResult['match_count']) {
            $bestResult = $result;
        }

        foreach ($this->substitutionPatterns as $pattern) {
            $result = $this->tryDecryption($words, $languageCode, $pattern);
            if ($result['match_count'] > $bestResult['match_count']) {
                $bestResult = $result;
            }

            foreach ($this->substitutionPatterns as $secondPattern) {
                if ($pattern === $secondPattern) continue;

                $combinedPattern = array_merge($pattern, $secondPattern);
                $result = $this->tryDecryption($words, $languageCode, $combinedPattern);
                if ($result['match_count'] > $bestResult['match_count']) {
                    $bestResult = $result;
                }
            }
        }

        $bestResult['success'] = $bestResult['match_count'] >= $minCount;
        $bestResult['min_count'] = $minCount;

        return $bestResult;
    }

    /**
     * Try a specific substitution pattern on the words
     */
    private function tryDecryption(array $words, string $languageCode, array $substitutions): array
    {
        $transformedWords = [];
        $matchedWords = [];
        $matchCount = 0;

        foreach ($words as $word) {
            $transformed = $this->applySubstitutions($word, $substitutions);

            if (str_contains($transformed, '?')) {
                $variants = $this->generateQuestionMarkVariants($transformed);
                $bestMatch = $this->findBestMatch($variants, $languageCode);

                if ($bestMatch) {
                    $transformedWords[] = $bestMatch;
                    $matchedWords[] = $bestMatch;
                    $matchCount++;
                } else {
                    $transformedWords[] = $transformed;
                }
            } else {
                if ($this->wordExists($transformed, $languageCode)) {
                    $matchedWords[] = $transformed;
                    $matchCount++;
                }
                $transformedWords[] = $transformed;
            }
        }

        return [
            'success' => false,
            'original_text' => implode(' ', $words),
            'decrypted_text' => implode(' ', $transformedWords),
            'match_count' => $matchCount,
            'matched_words' => $matchedWords,
            'substitutions' => $substitutions,
        ];
    }

    /**
     * Apply letter substitutions to a word
     */
    private function applySubstitutions(string $word, array $substitutions): string
    {
        if (empty($substitutions)) {
            return $word;
        }

        $result = '';
        for ($i = 0; $i < mb_strlen($word); $i++) {
            $char = mb_substr($word, $i, 1);
            $result .= $substitutions[$char] ?? $char;
        }

        return $result;
    }

    /**
     * Generate variants by replacing ? with different letters
     */
    private function generateQuestionMarkVariants(string $word, int $maxVariants = 50): array
    {
        $questionCount = substr_count($word, '?');

        if ($questionCount === 0) {
            return [$word];
        }

        if ($questionCount > 3) {
            $replacements = ['a', 'e', 'i', 'o', 'u', 'n', 't', 's', 'r'];
        } else {
            $replacements = $this->questionMarkReplacements;
        }

        $variants = [];
        $this->generateVariantsRecursive($word, $replacements, $variants, $maxVariants);

        return $variants;
    }

    /**
     * Recursively generate variants by replacing question marks
     */
    private function generateVariantsRecursive(string $word, array $replacements, array &$variants, int $maxVariants): void
    {
        if (count($variants) >= $maxVariants) {
            return;
        }

        $pos = strpos($word, '?');
        if ($pos === false) {
            $variants[] = $word;
            return;
        }

        foreach ($replacements as $replacement) {
            $newWord = substr_replace($word, $replacement, $pos, 1);
            $this->generateVariantsRecursive($newWord, $replacements, $variants, $maxVariants);

            if (count($variants) >= $maxVariants) {
                return;
            }
        }
    }

    /**
     * Find the best matching variant from a list
     */
    private function findBestMatch(array $variants, string $languageCode): ?string
    {
        foreach ($variants as $variant) {
            if ($this->wordExists($variant, $languageCode)) {
                return $variant;
            }
        }

        return null;
    }

    /**
     * Check if a word exists in the language index
     */
    private function wordExists(string $word, string $languageCode): bool
    {
        try {
            $boolQuery = new BoolQuery();

            $termWord = new Term();
            $termWord->setTerm('word', $word);
            $boolQuery->addMust($termWord);

            $termLang = new Term();
            $termLang->setTerm('languageCode', $languageCode);
            $boolQuery->addMust($termLang);

            $query = new Query($boolQuery);
            $query->setSize(1);

            $results = $this->esClient->getIndex($this->indexName)->search($query);

            return count($results->getResults()) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }
}
