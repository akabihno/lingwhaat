<?php

namespace App\Service\Search;

use App\Entity\WordSearchTokenEntity;
use App\Service\LanguageDetection\LanguageValidation\LanguageValidationService;
use App\Service\LanguageNormalizationService;

class TextDecryptionService
{
    private array $wordExistsCache = [];
    private array $substitutionPatterns = [];

    public function __construct(
        protected LanguageNormalizationService $normalizationService,
        protected LanguageValidationService $languageValidationService,
        private readonly MysqlWordLookup $mysqlLookup,
    ) {
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
        $this->wordExistsCache = [];

        $normalizedText = $this->normalizationService->normalizeText($text);
        $uniqueLetters = count_chars($normalizedText, 3);
        foreach (mb_str_split($uniqueLetters) as $src) {
            foreach (range('a', 'z') as $dst) {
                $candidateText = str_replace($src, $dst, $normalizedText);
                $result = $this->languageValidationService->analyze($candidateText);
                if ($result['isNatural']) {
                    $this->substitutionPatterns[] = [$src => $dst];
                }
            }
        }

        $words = preg_split('/\s+/', $normalizedText);
        $words = array_filter($words, fn($w) => !empty($w));

        $totalWords = count($words);

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

        if ($bestResult['match_count'] > $totalWords * 0.8) {
            $bestResult['success'] = $bestResult['match_count'] >= $minCount;
            $bestResult['min_count'] = $minCount;
            return $bestResult;
        }

        foreach ($this->substitutionPatterns as $pattern) {
            $result = $this->tryDecryption($words, $languageCode, $pattern);
            if ($result['match_count'] > $bestResult['match_count']) {
                $bestResult = $result;
            }

            if ($bestResult['match_count'] > $totalWords * 0.8) {
                break;
            }
        }

        $bestResult['success'] = $bestResult['match_count'] >= $minCount;
        $bestResult['min_count'] = $minCount;

        return $bestResult;
    }

    private function tryDecryption(array $words, string $languageCode, array $substitutions): array
    {
        $transformedWords = [];
        $matchedWords = [];
        $matchCount = 0;

        foreach ($words as $word) {
            $transformed = $this->applySubstitutions($word, $substitutions);

            if ($this->wordExists($transformed, $languageCode)) {
                $matchedWords[] = $transformed;
                $matchCount++;
            }
            $transformedWords[] = $transformed;
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

    private function wordExists(string $word, string $languageCode): bool
    {
        $cacheKey = $languageCode . ':' . $word;

        if (isset($this->wordExistsCache[$cacheKey])) {
            return $this->wordExistsCache[$cacheKey];
        }

        try {
            $exists = $this->mysqlLookup->exists(WordSearchTokenEntity::FIELD_WORD, $word, $languageCode);

            $this->wordExistsCache[$cacheKey] = $exists;

            return $exists;
        } catch (\Exception $e) {
            return false;
        }
    }
}
