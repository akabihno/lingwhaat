<?php

namespace App\Service\LanguageDetection\LanguageTransliteration;

use App\Constant\LanguageMappings;
use App\Service\LanguageDetection\LanguageTransliteration\Constants\IpaPredictorConstants;
use App\Service\LanguageDetection\ScriptDetectionService;
use App\Service\LanguageNormalizationService;
use App\Service\Logging\ElasticsearchLogger;
use App\Service\Search\FuzzySearchService;
use Symfony\Component\Uid\Uuid;

class LanguageTransliterationDetectionService
{
    const int EXACT_MATCH_SCORE = 5;
    const int MIN_WORD_LENGTH = 3;
    const float MIN_CONFIDENCE_RATIO = 0.3; // At least 30% of words should match
    const float MIN_MARGIN_RATIO = 0.2; // Winner should have 20% lead over second place

    public function __construct(
        protected ElasticsearchLogger $logger,
        protected UseIpaPredictorModelService $ipaPredictorModelService,
        protected UseWordPredictorModelService $useWordPredictorModelService,
        protected ScriptDetectionService $scriptDetectionService,
        protected FuzzySearchService $fuzzySearchService,
        protected LanguageNormalizationService $languageNormalizationService,
        protected string $projectDir
    )
    {
    }

    public function process(string $languageInput): array
    {
        $uuid = Uuid::v4()->toRfc4122();

        if (empty($languageInput)) {
            $this->logger->warning(
                'Empty input',
                ['uuid' => $uuid, 'service' => '[LanguageTransliterationDetectionService]']
            );
            return [
                'languageCode' => null,
                'input' => $languageInput,
                'count' => 0,
                'matches' => 0,
            ];
        }

        $this->logger->info(
            'Starting transliteration language detection',
            ['uuid' => $uuid, 'service' => '[LanguageTransliterationDetectionService]']
        );

        $languageScript = $this->scriptDetectionService->detect($languageInput);

        if ($languageScript === ScriptDetectionService::UNKNOWN_SCRIPT) {
            $this->logger->warning(
                'Unknown script detected',
                ['uuid' => $uuid, 'service' => '[LanguageTransliterationDetectionService]', 'input' => $languageInput]
            );
            return [
                'languageCode' => null,
                'input' => $languageInput,
                'count' => 0,
                'matches' => 0,
            ];
        }

        // Get source languages (languages that USE the detected script, e.g., Russian for Cyrillic input)
        // These models will be used to predict IPA from the transliterated text
        $sourceLanguages = $this->scriptDetectionService->getLanguagesByScript($languageScript);

        // Get target languages (languages that DON'T use the detected script, e.g., Latvian for Cyrillic input)
        // These are the languages we'll search for matches in
        $transliterationCandidates = $this->scriptDetectionService->getTransliterationCandidatesByScript($languageScript);

        if (empty($sourceLanguages)) {
            $this->logger->warning(
                'No source languages found for script',
                ['uuid' => $uuid, 'service' => '[LanguageTransliterationDetectionService]', 'script' => $languageScript]
            );
            return [
                'languageCode' => null,
                'input' => $languageInput,
                'count' => 0,
                'matches' => 0,
            ];
        }

        if (empty($transliterationCandidates)) {
            $this->logger->warning(
                'No transliteration candidates found',
                ['uuid' => $uuid, 'service' => '[LanguageTransliterationDetectionService]', 'script' => $languageScript]
            );
            return [
                'languageCode' => null,
                'input' => $languageInput,
                'count' => 0,
                'matches' => 0,
            ];
        }

        $normalizedInput = $this->languageNormalizationService->normalizeText($languageInput);
        $allWords = explode(' ', $normalizedInput);
        $count = count($allWords);

        $languageCounts = [];
        $matchCount = 0;

        // Build a flat list of target language codes for efficient checking
        $targetLanguageCodes = [];
        foreach ($transliterationCandidates as $scriptName => $languageNames) {
            foreach ($languageNames as $languageName) {
                $code = LanguageMappings::getLanguageCodeByName($languageName);
                if ($code) {
                    $targetLanguageCodes[] = $code;
                }
            }
        }

        foreach ($allWords as $rawWord) {
            $word = $this->languageNormalizationService->normalizeWord($rawWord);
            if (!$word) {
                continue;
            }

            $wordLength = mb_strlen($word, 'utf8');
            if ($wordLength < self::MIN_WORD_LENGTH) {
                continue;
            }

            $isLongWord = $wordLength > LanguageNormalizationService::ARTICLE_LENGTH;

            $ipaPredictions = $this->collectIpaPredictions($word, $sourceLanguages, $uuid);

            if (empty($ipaPredictions)) {
                continue;
            }

            $ipaStrings = array_column($ipaPredictions, 'ipa');
            $consensusIpa = $this->getMostFrequentIpa($ipaStrings);

            if (!$consensusIpa) {
                continue;
            }

            $this->logger->info(
                'Consensus IPA for word: ' . $word . ' is: ' . $consensusIpa,
                ['uuid' => $uuid, 'service' => '[LanguageTransliterationDetectionService]', 'predictions' => count($ipaPredictions)]
            );

            try {
                // Search for matches using consensus IPA
                $exactMatches = $this->fuzzySearchService->findExactMatchesByIpa($consensusIpa);
                $fuzzyMatches = $this->fuzzySearchService->findClosestMatchesByIpa($consensusIpa);

                // Combine all matches for rarity calculation
                $allMatches = array_merge($exactMatches, $fuzzyMatches);

                // Calculate weights once per word
                $lengthWeight = $this->getWordLengthWeight($word);
                $rarityWeight = $this->getRarityWeight($allMatches, '');

                // Process exact matches
                if (!empty($exactMatches)) {
                    foreach ($exactMatches as $match) {
                        if (!isset($match['languageCode'])) {
                            continue;
                        }

                        $matchedLanguageCode = $match['languageCode'];

                        // Count ONLY if the match is in a TARGET language (not source language)
                        if (in_array($matchedLanguageCode, $targetLanguageCodes, true)) {
                            // Only count longer words for language detection scoring
                            if ($isLongWord) {
                                $score = self::EXACT_MATCH_SCORE * $lengthWeight * $rarityWeight;
                                $languageCounts[$matchedLanguageCode] = ($languageCounts[$matchedLanguageCode] ?? 0) + $score;
                                $matchCount++;
                            }
                        }
                    }
                }

                // Process fuzzy matches
                foreach ($fuzzyMatches as $match) {
                    if (!isset($match['languageCode'])) {
                        continue;
                    }

                    $matchedLanguageCode = $match['languageCode'];

                    // Count ONLY if the match is in a TARGET language (not source language)
                    if (in_array($matchedLanguageCode, $targetLanguageCodes, true)) {
                        // Only count longer words for language detection scoring
                        if ($isLongWord) {
                            // Base score of 1 for fuzzy match, adjusted by weights
                            $score = 1.0 * $lengthWeight * $rarityWeight;
                            $languageCounts[$matchedLanguageCode] = ($languageCounts[$matchedLanguageCode] ?? 0) + $score;
                            $matchCount++;
                        }
                    }
                }
            } catch (\Throwable $e) {
                $this->logger->error(
                    'IPA search failed',
                    [
                        'uuid' => $uuid,
                        'service' => '[LanguageTransliterationDetectionService]',
                        'word' => $word,
                        'ipa' => $consensusIpa,
                        'error' => $e->getMessage()
                    ]
                );
            }
        }

        $topLanguageCode = null;
        $confidence = 0.0;
        $reconstructedText = null;

        if (!empty($languageCounts)) {
            arsort($languageCounts);
            $topLanguageCode = array_key_first($languageCounts);
            $topScore = $languageCounts[$topLanguageCode];

            // Calculate confidence as score normalized by word count
            $confidence = $count > 0 ? $topScore / $count : 0.0;

            // Apply confidence threshold - require minimum confidence
            $minConfidence = $count * self::MIN_CONFIDENCE_RATIO;
            if ($topScore < $minConfidence) {
                $this->logger->warning(
                    'Confidence too low, rejecting detection',
                    [
                        'uuid' => $uuid,
                        'service' => '[LanguageTransliterationDetectionService]',
                        'topLanguage' => $topLanguageCode,
                        'topScore' => $topScore,
                        'minConfidence' => $minConfidence,
                        'confidence' => $confidence
                    ]
                );
                $topLanguageCode = null;
                $confidence = 0.0;
            }

            // Check margin - winner should have significant lead over second place
            if ($topLanguageCode) {
                $scores = array_values($languageCounts);
                if (count($scores) > 1) {
                    $secondScore = $scores[1];
                    $margin = $topScore > 0 ? ($topScore - $secondScore) / $topScore : 0;

                    if ($margin < self::MIN_MARGIN_RATIO) {
                        $this->logger->warning(
                            'Margin too small between top languages, rejecting detection',
                            [
                                'uuid' => $uuid,
                                'service' => '[LanguageTransliterationDetectionService]',
                                'topLanguage' => $topLanguageCode,
                                'topScore' => $topScore,
                                'secondScore' => $secondScore,
                                'margin' => $margin,
                                'minMargin' => self::MIN_MARGIN_RATIO
                            ]
                        );
                        $topLanguageCode = null;
                        $confidence = 0.0;
                    }

                    // Log ambiguous cases for analysis
                    if ($topLanguageCode && $margin < 0.5) {
                        $this->logger->info(
                            'Ambiguous detection - multiple close candidates',
                            [
                                'uuid' => $uuid,
                                'service' => '[LanguageTransliterationDetectionService]',
                                'scores' => $languageCounts,
                                'input' => $languageInput,
                                'chosen' => $topLanguageCode,
                                'margin' => $margin
                            ]
                        );
                    }
                }
            }
        }

        // Build reconstructed text - refine matches to only use detected language
        if ($topLanguageCode) {
            $reconstructedText = $this->buildReconstructedText(
                $allWords,
                $topLanguageCode,
                $sourceLanguages,
                $uuid
            );
        }

        $this->logger->info(
            'Completed transliteration detection',
            [
                'uuid' => $uuid,
                'service' => '[LanguageTransliterationDetectionService]',
                'detectedLanguage' => $topLanguageCode ?? 'none',
                'confidence' => $confidence,
                'matches' => $matchCount,
                'input' => $languageInput,
                'reconstructed' => $reconstructedText,
                'script' => $languageScript
            ]
        );

        return [
            'languageCode' => $topLanguageCode,
            'confidence' => $confidence,
            'input' => $languageInput,
            'reconstructed' => $reconstructedText,
            'count' => $count,
            'matches' => $matchCount,
        ];
    }

    private function buildReconstructedText(
        array $allWords,
        string $targetLanguageCode,
        array $sourceLanguages,
        string $uuid
    ): string {
        $reconstructedWords = [];

        foreach ($allWords as $rawWord) {
            $word = $this->languageNormalizationService->normalizeWord($rawWord);
            if (!$word) {
                $reconstructedWords[] = $rawWord;
                continue;
            }

            // Skip very short words
            if (mb_strlen($word, 'utf8') < self::MIN_WORD_LENGTH) {
                $reconstructedWords[] = $rawWord;
                continue;
            }

            // Collect IPA predictions from all source language models
            $ipaPredictions = $this->collectIpaPredictions($word, $sourceLanguages, $uuid);

            if (empty($ipaPredictions)) {
                $reconstructedWords[] = $rawWord;
                continue;
            }

            $ipaStrings = array_column($ipaPredictions, 'ipa');
            $consensusIpa = $this->getMostFrequentIpa($ipaStrings);

            if (!$consensusIpa) {
                $reconstructedWords[] = $rawWord;
                continue;
            }

            $bestMatch = null;
            $bestScore = 0;

            try {
                // Try exact matches first
                $exactMatches = $this->fuzzySearchService->findExactMatchesByIpa($consensusIpa);
                foreach ($exactMatches as $match) {
                    if (
                        isset($match['languageCode'], $match['word']) &&
                        $match['languageCode'] === $targetLanguageCode &&
                        $bestScore < self::EXACT_MATCH_SCORE
                    ) {
                        $bestMatch = $match['word'];
                        $bestScore = self::EXACT_MATCH_SCORE;
                        break; // Found exact match, stop searching
                    }
                }

                // Try fuzzy matches if no exact match found
                if ($bestScore < self::EXACT_MATCH_SCORE) {
                    $fuzzyMatches = $this->fuzzySearchService->findClosestMatchesByIpa($consensusIpa);
                    foreach ($fuzzyMatches as $match) {
                        if (
                            isset($match['languageCode'], $match['word']) &&
                            $match['languageCode'] === $targetLanguageCode &&
                            $bestScore < 1
                        ) {
                            $bestMatch = $match['word'];
                            $bestScore = 1;
                        }
                    }
                }
            } catch (\Throwable $e) {
                $this->logger->error(
                    'Reconstruction failed for word',
                    [
                        'uuid' => $uuid,
                        'service' => '[LanguageTransliterationDetectionService]',
                        'word' => $word,
                        'error' => $e->getMessage()
                    ]
                );
            }

            $reconstructedWords[] = $bestMatch ?? $rawWord;
        }

        return implode(' ', $reconstructedWords);
    }

    private function modelExists(string $languageNameLower): bool
    {
        $modelPath = $this->projectDir . '/' . IpaPredictorConstants::getMlServiceIpaModelsPath() . $languageNameLower . '_model.pt';
        $dataPath = $this->projectDir . '/' . IpaPredictorConstants::getMlServiceDataPath() . $languageNameLower . '.csv';

        return file_exists($modelPath) && file_exists($dataPath);
    }

    /**
     * Validate that predicted IPA makes sense
     */
    private function isValidIpa(string $ipa): bool
    {
        if (empty($ipa)) {
            return false;
        }

        // Check for reasonable length ratio (IPA shouldn't be excessively long)
        if (mb_strlen($ipa, 'utf8') > 100) {
            return false;
        }

        // Basic validation - should contain IPA-like characters
        // Allow IPA symbols, spaces, and common phonetic notation
        return true;
    }

    /**
     * Calculate word length weight for scoring
     */
    private function getWordLengthWeight(string $word): float
    {
        $wordLength = mb_strlen($word, 'utf8');
        // Longer words get more weight, capped at 2x
        return min($wordLength / 10.0, 2.0);
    }

    /**
     * Calculate rarity weight based on how many languages a match appears in
     */
    private function getRarityWeight(array $allMatches, string $currentLanguageCode): float
    {
        $uniqueLanguages = [];
        foreach ($allMatches as $match) {
            if (isset($match['languageCode'])) {
                $uniqueLanguages[$match['languageCode']] = true;
            }
        }

        $languageCount = count($uniqueLanguages);
        if ($languageCount === 0) {
            return 1.0;
        }

        // Words appearing in fewer languages are more distinctive
        return 1.0 / sqrt($languageCount);
    }

    /**
     * Get most frequent IPA prediction from multiple models
     */
    private function getMostFrequentIpa(array $ipaPredictions): ?string
    {
        if (empty($ipaPredictions)) {
            return null;
        }

        $counts = array_count_values($ipaPredictions);
        arsort($counts);

        return array_key_first($counts);
    }

    /**
     * Collect IPA predictions from all source language models
     */
    private function collectIpaPredictions(
        string $word,
        array $sourceLanguages,
        string $uuid
    ): array {
        $ipaPredictions = [];

        foreach ($sourceLanguages as $scriptName => $languageNames) {
            foreach ($languageNames as $languageName) {
                $languageNameLower = strtolower($languageName);

                if (!$this->modelExists($languageNameLower)) {
                    continue;
                }

                try {
                    $ipa = $this->ipaPredictorModelService->run($languageNameLower, $word);
                    if ($ipa && $this->isValidIpa($ipa)) {
                        $ipaPredictions[] = [
                            'ipa' => $ipa,
                            'sourceLanguage' => $languageNameLower
                        ];
                    }
                } catch (\Throwable $e) {
                    $this->logger->error(
                        'IPA prediction failed',
                        [
                            'uuid' => $uuid,
                            'service' => '[LanguageTransliterationDetectionService]',
                            'word' => $word,
                            'sourceLanguage' => $languageNameLower,
                            'error' => $e->getMessage()
                        ]
                    );
                }
            }
        }

        return $ipaPredictions;
    }

}