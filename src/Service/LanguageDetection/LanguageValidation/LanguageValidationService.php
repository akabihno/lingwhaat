<?php

namespace App\Service\LanguageDetection\LanguageValidation;

use App\Service\Logging\ElasticsearchLogger;

class LanguageValidationService
{
    private const string VOWELS = 'aeiouAEIOU';
    private const string CONSONANTS = 'bcdfghjklmnpqrstvwxyzBCDFGHJKLMNPQRSTVWXYZ';
    private const float MIN_VOWEL_RATIO = 0.20;  // At least 20% vowels
    private const float MAX_VOWEL_RATIO = 0.60;  // At most 60% vowels
    private const float OPTIMAL_VOWEL_RATIO = 0.40; // Ideal around 40%

    public function __construct(protected ElasticsearchLogger $logger)
    {
    }

    /**
     * Analyzes text and returns a score from 0 to 100
     * Higher score means more natural-looking text
     */
    public function analyze(string $text): array
    {
        $text = trim($text);

        if (empty($text)) {
            return [
                'score' => 0,
                'isNatural' => false,
                'details' => ['error' => 'Empty text']
            ];
        }

        $letters = preg_replace('/[^a-zA-Z]/', '', $text);

        if (strlen($letters) < 3) {
            return [
                'score' => 0,
                'isNatural' => false,
                'details' => ['error' => 'Not enough letters to analyze']
            ];
        }

        $scores = [
            'vowelRatio' => $this->scoreVowelRatio($letters),
            'consonantClusters' => $this->scoreConsonantClusters($letters),
            'vowelClusters' => $this->scoreVowelClusters($letters),
            'alternationPattern' => $this->scoreAlternation($letters)
        ];

        $totalScore = (
            $scores['vowelRatio'] * 0.30 +
            $scores['consonantClusters'] * 0.35 +
            $scores['vowelClusters'] * 0.20 +
            $scores['alternationPattern'] * 0.15
        );

        $this->logger->info(
            'Completed validation',
            [
                'input' => $text,
                'service' => '[LanguageValidationService]',
                'isNatural' => $totalScore >= 60,
                'score' => round($totalScore, 2)
            ]
        );

        return [
            'score' => round($totalScore, 2),
            'isNatural' => $totalScore >= 60,
            'details' => array_merge($scores, [
                'vowelCount' => $this->countVowels($letters),
                'consonantCount' => $this->countConsonants($letters),
                'totalLetters' => strlen($letters)
            ])
        ];
    }

    /**
     * Score based on vowel to consonant ratio
     */
    private function scoreVowelRatio(string $letters): float
    {
        $total = strlen($letters);
        $vowelCount = $this->countVowels($letters);
        $ratio = $vowelCount / $total;

        if ($ratio < self::MIN_VOWEL_RATIO || $ratio > self::MAX_VOWEL_RATIO) {
            $deviation = min(
                abs($ratio - self::MIN_VOWEL_RATIO),
                abs($ratio - self::MAX_VOWEL_RATIO)
            );
            return max(0, 100 - ($deviation * 300));
        }

        $deviation = abs($ratio - self::OPTIMAL_VOWEL_RATIO);
        return max(70, 100 - ($deviation * 200));
    }

    /**
     * Score based on consonant cluster lengths
     */
    private function scoreConsonantClusters(string $letters): float
    {
        $maxCluster = $this->getMaxClusterLength($letters, self::CONSONANTS);

        if ($maxCluster <= 3) {
            return 100;
        } elseif ($maxCluster <= 4) {
            return 80;
        } elseif ($maxCluster <= 5) {
            return 50;
        } elseif ($maxCluster <= 6) {
            return 30;
        } else {
            return max(0, 100 - ($maxCluster * 10));
        }
    }

    /**
     * Score based on vowel cluster lengths
     */
    private function scoreVowelClusters(string $letters): float
    {
        $maxCluster = $this->getMaxClusterLength($letters, self::VOWELS);

        if ($maxCluster <= 2) {
            return 100;
        } elseif ($maxCluster <= 3) {
            return 85;
        } elseif ($maxCluster <= 4) {
            return 60;
        } else {
            return max(0, 100 - ($maxCluster * 15));
        }
    }

    /**
     * Score based on vowel-consonant alternation pattern
     */
    private function scoreAlternation(string $letters): float
    {
        $alternations = 0;
        $prevType = null;

        for ($i = 0; $i < strlen($letters); $i++) {
            $currentType = $this->isVowel($letters[$i]) ? 'v' : 'c';

            if ($prevType !== null && $prevType !== $currentType) {
                $alternations++;
            }

            $prevType = $currentType;
        }

        $maxPossibleAlternations = strlen($letters) - 1;
        if ($maxPossibleAlternations === 0) {
            return 50;
        }

        $alternationRate = $alternations / $maxPossibleAlternations;

        if ($alternationRate >= 0.50 && $alternationRate <= 0.85) {
            return 100;
        } elseif ($alternationRate >= 0.35) {
            return 75;
        } else {
            return max(0, $alternationRate * 150);
        }
    }

    /**
     * Count vowels in string
     */
    private function countVowels(string $text): int
    {
        return strlen(preg_replace('/[^' . self::VOWELS . ']/', '', $text));
    }

    /**
     * Count consonants in string
     */
    private function countConsonants(string $text): int
    {
        return strlen(preg_replace('/[^' . self::CONSONANTS . ']/', '', $text));
    }

    /**
     * Check if character is a vowel
     */
    private function isVowel(string $char): bool
    {
        return strpos(self::VOWELS, $char) !== false;
    }

    /**
     * Get maximum cluster length for given character set
     */
    private function getMaxClusterLength(string $letters, string $charset): int
    {
        $maxCluster = 0;
        $currentCluster = 0;

        for ($i = 0; $i < strlen($letters); $i++) {
            if (str_contains($charset, $letters[$i])) {
                $currentCluster++;
                $maxCluster = max($maxCluster, $currentCluster);
            } else {
                $currentCluster = 0;
            }
        }

        return $maxCluster;
    }

}