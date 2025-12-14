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
        $words = explode(' ', $normalizedInput);
        $words = $this->languageNormalizationService->removeArticles($words);
        $count = count($words);

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

        foreach ($words as $word) {
            $word = $this->languageNormalizationService->normalizeWord($word);
            if (!$word) {
                continue;
            }

            // Use SOURCE language models (e.g., Russian model for Cyrillic input)
            foreach ($sourceLanguages as $scriptName => $languageNames) {
                foreach ($languageNames as $languageName) {
                    $sourceLanguageCode = LanguageMappings::getLanguageCodeByName($languageName);

                    if (!$sourceLanguageCode) {
                        continue;
                    }

                    $languageNameLower = strtolower($languageName);

                    if (!$this->modelExists($languageNameLower)) {
                        $this->logger->warning(
                            'Model not found for source language: ' . $languageNameLower,
                            ['uuid' => $uuid, 'service' => '[LanguageTransliterationDetectionService]', 'input' => $languageInput]
                        );
                        continue;
                    }

                    try {
                        // Predict IPA using source language model (e.g., Russian model)
                        $ipa = $this->ipaPredictorModelService->run($languageNameLower, $word);
                        $this->logger->info(
                            'Predicted IPA for word: ' . $word . ' using source language: ' . $languageNameLower . ' is: ' . $ipa,
                            ['uuid' => $uuid, 'service' => '[LanguageTransliterationDetectionService]']
                        );

                        if (!$ipa) {
                            continue;
                        }

                        $exactMatches = $this->fuzzySearchService->findExactMatchesByIpa($ipa);

                        if (!empty($exactMatches)) {
                            foreach ($exactMatches as $match) {
                                if (!isset($match['languageCode'])) {
                                    continue;
                                }

                                $matchedLanguageCode = $match['languageCode'];

                                // Count ONLY if the match is in a TARGET language (not source language)
                                if (in_array($matchedLanguageCode, $targetLanguageCodes, true)) {
                                    $languageCounts[$matchedLanguageCode] = ($languageCounts[$matchedLanguageCode] ?? 0) + self::EXACT_MATCH_SCORE;
                                    $matchCount++;
                                }
                            }
                        }

                        $fuzzyMatches = $this->fuzzySearchService->findClosestMatchesByIpa($ipa);

                        foreach ($fuzzyMatches as $match) {
                            if (!isset($match['languageCode'])) {
                                continue;
                            }

                            $matchedLanguageCode = $match['languageCode'];

                            // Count ONLY if the match is in a TARGET language (not source language)
                            if (in_array($matchedLanguageCode, $targetLanguageCodes, true)) {
                                $languageCounts[$matchedLanguageCode] = ($languageCounts[$matchedLanguageCode] ?? 0) + 1;
                                $matchCount++;
                            }
                        }
                    } catch (\Throwable $e) {
                        $this->logger->error(
                            'IPA prediction or search failed',
                            [
                                'uuid' => $uuid,
                                'service' => '[LanguageTransliterationDetectionService]',
                                'word' => $word,
                                'sourceLanguageName' => $languageNameLower,
                                'sourceLanguageCode' => $sourceLanguageCode,
                                'error' => $e->getMessage()
                            ]
                        );
                    }
                }
            }
        }

        $topLanguageCode = null;
        if (!empty($languageCounts)) {
            arsort($languageCounts);
            $topLanguageCode = array_key_first($languageCounts);
        }

        $this->logger->info(
            'Completed transliteration detection',
            [
                'uuid' => $uuid,
                'service' => '[LanguageTransliterationDetectionService]',
                'detectedLanguage' => $topLanguageCode ?? 'none',
                'matches' => $matchCount,
                'input' => $languageInput,
                'script' => $languageScript
            ]
        );

        return [
            'languageCode' => $topLanguageCode,
            'input' => $languageInput,
            'count' => $count,
            'matches' => $matchCount,
        ];
    }

    private function modelExists(string $languageNameLower): bool
    {
        $modelPath = $this->projectDir . '/' . IpaPredictorConstants::getMlServiceIpaModelsPath() . $languageNameLower . '_model.pt';
        $dataPath = $this->projectDir . '/' . IpaPredictorConstants::getMlServiceDataPath() . $languageNameLower . '.csv';

        return file_exists($modelPath) && file_exists($dataPath);
    }

}