<?php

namespace App\Service\LanguageDetection;

use App\Constant\LanguageServicesAndCodes;
use App\Service\LanguageDetection\LanguageTransliteration\TransliterationDetectionService;
use App\Service\LanguageNormalizationService;
use App\Service\Logging\ElasticsearchLogger;
use App\Service\Search\FuzzySearchService;
use Symfony\Component\Uid\Uuid;

class LanguageDetectionService
{
    public function __construct(
        protected ElasticsearchLogger $logger,
        protected LanguageNormalizationService $languageNormalizationService,
        protected TransliterationDetectionService $transliterationDetectionService,
        protected FuzzySearchService $fuzzySearchService
    )
    {
    }

    public function process(string $languageInput, int $translitDetection): array
    {
        $uuid = Uuid::v4()->toRfc4122();

        if (empty($languageInput)) {
            $this->logger->warning(
                'Empty input',
                ['uuid' => $uuid, 'service' => '[LanguageDetectionService]']
            );
            return [
                'languageCode' => null,
                'input' => $languageInput,
                'count' => 0,
                'matches' => 0,
            ];
        }

        $this->logger->info(
            'Starting language detection',
            ['uuid' => $uuid, 'service' => '[LanguageDetectionService]']
        );

        $normalizedInput = $this->languageNormalizationService->normalizeText($languageInput);
        $words = explode(' ', $normalizedInput);
        $words = $this->languageNormalizationService->removeArticles($words);
        $count = count($words);

        if ($translitDetection) {
            $this->logger->info(
                'Running transliteration detection',
                ['uuid' => $uuid, 'service' => '[LanguageDetectionService]']
            );
            return $this->transliterationDetectionService->run($words, $uuid, microtime(true));
        }

        $languageCounts = [];
        $matchCount = 0;

        foreach ($words as $word) {
            $word = $this->languageNormalizationService->normalizeWord($word);
            if (!$word) {
                continue;
            }

            try {
                $matches = $this->fuzzySearchService->findClosestMatches($word, 5);

                foreach ($matches as $match) {
                    if (!isset($match['languageCode'])) {
                        continue;
                    }

                    $code = $match['languageCode'];
                    $languageCounts[$code] = ($languageCounts[$code] ?? 0) + 1;
                    $matchCount++;
                }
            } catch (\Throwable $e) {
                $this->logger->error(
                    'Fuzzy search failed',
                    [
                        'uuid' => $uuid,
                        'service' => '[LanguageDetectionService]',
                        'word' => $word,
                        'error' => $e->getMessage()
                    ]
                );
            }
        }

        $topLanguageCode = null;
        if (!empty($languageCounts)) {
            arsort($languageCounts);
            $topLanguageCode = array_key_first($languageCounts);
        }

        $this->logger->info(
            'Completed detection',
            [
                'uuid' => $uuid,
                'service' => '[LanguageDetectionService]',
                'detectedLanguage' => $topLanguageCode ?? 'none',
                'matches' => $matchCount
            ]
        );

        return [
            'languageCode' => $topLanguageCode,
            'input' => $languageInput,
            'count' => $count,
            'matches' => $matchCount,
        ];
    }

    public static function getLanguageCodesForTransliteration(): array
    {
        return [
            LanguageServicesAndCodes::LATVIAN_LANGUAGE_CODE,
            LanguageServicesAndCodes::RUSSIAN_LANGUAGE_CODE,
        ];
    }

}