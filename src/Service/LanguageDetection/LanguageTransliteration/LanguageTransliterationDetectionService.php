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
        protected LanguageNormalizationService $languageNormalizationService
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

        $transliterationCandidates = $this->scriptDetectionService->getTransliterationCandidatesByScript($languageScript);

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

        foreach ($words as $word) {
            $word = $this->languageNormalizationService->normalizeWord($word);
            if (!$word) {
                continue;
            }

            foreach ($transliterationCandidates as $scriptName => $languageNames) {
                foreach ($languageNames as $languageName) {
                    $languageCode = LanguageMappings::getLanguageCodeByName($languageName);

                    if (!$languageCode) {
                        continue;
                    }

                    $languageNameLower = strtolower($languageName);

                    if (!$this->modelExists($languageNameLower)) {
                        continue;
                    }

                    try {
                        $ipa = $this->ipaPredictorModelService->run($languageNameLower, $word);
                        $this->logger->info(
                            'Predicted IPA for word: ' . $word . ' in language: ' . $languageNameLower . ' is: ' . $ipa,
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

                                if ($matchedLanguageCode === $languageCode) {
                                    $languageCounts[$languageCode] = ($languageCounts[$languageCode] ?? 0) + self::EXACT_MATCH_SCORE;
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

                            if ($matchedLanguageCode === $languageCode) {
                                $languageCounts[$languageCode] = ($languageCounts[$languageCode] ?? 0) + 1;
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
                                'languageName' => $languageNameLower,
                                'languageCode' => $languageCode,
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
        $modelPath = IpaPredictorConstants::getMlServiceIpaModelsPath() . $languageNameLower . '_model.pt';
        $dataPath = IpaPredictorConstants::getMlServiceDataPath() . $languageNameLower . '.csv';

        return file_exists($modelPath) && file_exists($dataPath);
    }

}