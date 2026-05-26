<?php

namespace App\Service\LanguageDetection\LanguageValidation;

use App\Constant\ScriptAlphabets;
use App\Service\Logging\ElasticsearchLogger;

class LanguageValidationService
{
    public function __construct(protected ElasticsearchLogger $logger)
    {
    }

    /**
     * Analyzes text and returns a naturalness score from 0 to 100.
     *
     * When $languageCode is provided the vowel set and all scoring thresholds
     * are taken from ScriptAlphabets, making analysis correct for every
     * supported script (Cyrillic, Georgian, Arabic, etc.).
     * Without a language code the method falls back to ASCII-Latin behaviour
     * so existing callers (e.g. /api/validate) are unaffected.
     */
    public function analyze(string $text, ?string $languageCode = null): array
    {
        $text = trim($text);

        if (empty($text)) {
            return [
                'score' => 0,
                'isNatural' => false,
                'details' => ['error' => 'Empty text'],
            ];
        }

        // Unicode-safe letter extraction — works for any script.
        preg_match_all('/\p{L}/u', $text, $matches);
        $letters = implode('', $matches[0] ?? []);

        if (mb_strlen($letters) < 3) {
            return [
                'score' => 0,
                'isNatural' => false,
                'details' => ['error' => 'Not enough letters to analyze'],
            ];
        }

        $vowelChars  = mb_str_split(
            $languageCode !== null
                ? ScriptAlphabets::getVowelsForLanguage($languageCode)
                : 'aeiouAEIOU'
        );
        $thresholds  = $languageCode !== null
            ? ScriptAlphabets::getThresholdsForLanguage($languageCode)
            : ScriptAlphabets::SCRIPT_THRESHOLDS[ScriptAlphabets::LATIN_ALPHABET];

        $vowelCount = $this->countInSet($letters, $vowelChars);
        $totalLetters = mb_strlen($letters);

        $scores = [
            'vowelRatio'         => $this->scoreVowelRatio($letters, $vowelChars, $thresholds),
            'consonantClusters'  => $this->scoreConsonantClusters($letters, $vowelChars, $thresholds),
            'vowelClusters'      => $this->scoreVowelClusters($letters, $vowelChars, $thresholds),
            'alternationPattern' => $this->scoreAlternation($letters, $vowelChars, $thresholds),
        ];

        $totalScore = (
            $scores['vowelRatio'] * 0.30 +
            $scores['consonantClusters'] * 0.35 +
            $scores['vowelClusters'] * 0.20 +
            $scores['alternationPattern'] * 0.15
        );

        $this->logger->info('Completed validation', [
            'input'     => $text,
            'service'   => '[LanguageValidationService]',
            'isNatural' => $totalScore >= 60,
            'score'     => round($totalScore, 2),
        ]);

        return [
            'score'     => round($totalScore, 2),
            'isNatural' => $totalScore >= 60,
            'details'   => array_merge($scores, [
                'vowelCount'    => $vowelCount,
                'consonantCount' => $totalLetters - $vowelCount,
                'totalLetters'  => $totalLetters,
            ]),
        ];
    }

    private function scoreVowelRatio(string $letters, array $vowelChars, array $t): float
    {
        $total = mb_strlen($letters);
        $ratio = $this->countInSet($letters, $vowelChars) / $total;

        if ($ratio < $t['min_vowel_ratio'] || $ratio > $t['max_vowel_ratio']) {
            $deviation = min(
                abs($ratio - $t['min_vowel_ratio']),
                abs($ratio - $t['max_vowel_ratio'])
            );
            return max(0.0, 100.0 - ($deviation * 300));
        }

        return max(70.0, 100.0 - (abs($ratio - $t['optimal_vowel_ratio']) * 200));
    }

    private function scoreConsonantClusters(string $letters, array $vowelChars, array $t): float
    {
        $max = $this->getMaxClusterLength($letters, $vowelChars, false);
        $n   = $t['max_consonant_cluster'];

        if ($max <= $n)     return 100.0;
        if ($max <= $n + 1) return 80.0;
        if ($max <= $n + 2) return 50.0;
        if ($max <= $n + 3) return 30.0;
        return max(0.0, 100.0 - ($max * 10));
    }

    private function scoreVowelClusters(string $letters, array $vowelChars, array $t): float
    {
        $max = $this->getMaxClusterLength($letters, $vowelChars, true);
        $n   = $t['max_vowel_cluster'];

        if ($max <= $n)     return 100.0;
        if ($max <= $n + 1) return 85.0;
        if ($max <= $n + 2) return 60.0;
        return max(0.0, 100.0 - ($max * 15));
    }

    private function scoreAlternation(string $letters, array $vowelChars, array $t): float
    {
        $alternations = 0;
        $prevType     = null;

        foreach (mb_str_split($letters) as $char) {
            $currentType = in_array($char, $vowelChars, true) ? 'v' : 'c';
            if ($prevType !== null && $prevType !== $currentType) {
                $alternations++;
            }
            $prevType = $currentType;
        }

        $maxPossible = mb_strlen($letters) - 1;
        if ($maxPossible === 0) {
            return 50.0;
        }

        $rate = $alternations / $maxPossible;

        if ($rate >= $t['alternation_ideal_min'] && $rate <= $t['alternation_ideal_max']) {
            return 100.0;
        }
        if ($rate >= $t['alternation_good_min']) {
            return 75.0;
        }
        return max(0.0, $rate * 150);
    }

    private function countInSet(string $letters, array $charSet): int
    {
        $count = 0;
        foreach (mb_str_split($letters) as $char) {
            if (in_array($char, $charSet, true)) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Returns the longest run of vowels ($forVowels = true) or consonants ($forVowels = false).
     */
    private function getMaxClusterLength(string $letters, array $vowelChars, bool $forVowels): int
    {
        $max     = 0;
        $current = 0;

        foreach (mb_str_split($letters) as $char) {
            if (in_array($char, $vowelChars, true) === $forVowels) {
                if (++$current > $max) {
                    $max = $current;
                }
            } else {
                $current = 0;
            }
        }

        return $max;
    }
}
