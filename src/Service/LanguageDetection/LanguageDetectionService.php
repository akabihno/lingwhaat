<?php

namespace App\Service\LanguageDetection;

use App\Service\LanguageNormalizationService;
use App\Service\Logging\ElasticsearchLogger;
use App\Service\Search\FuzzySearchService;
use Symfony\Component\Uid\Uuid;

class LanguageDetectionService
{
    const int EXACT_MATCH_SCORE = 5;
    public function __construct(
        protected ElasticsearchLogger $logger,
        protected LanguageNormalizationService $languageNormalizationService,
        protected FuzzySearchService $fuzzySearchService
    )
    {
    }

    public function process(string $languageInput): array
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

        $languageCounts = [];
        $matchCount = 0;

        foreach ($words as $word) {
            $word = $this->languageNormalizationService->normalizeWord($word);
            if (!$word) {
                continue;
            }

            try {
                $exactMatches = $this->fuzzySearchService->findExactMatches($word);

                if (!empty($exactMatches)) {
                    foreach ($exactMatches as $match) {
                        if (!isset($match['languageCode'])) {
                            continue;
                        }

                        $code = $match['languageCode'];
                        $languageCounts[$code] = ($languageCounts[$code] ?? 0) + self::EXACT_MATCH_SCORE;
                        $matchCount++;
                    }
                }

                $matches = $this->fuzzySearchService->findClosestMatches($word);

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
                    'Search failed',
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
                'matches' => $matchCount,
                'input' => $languageInput
            ]
        );

        return [
            'languageCode' => $topLanguageCode,
            'input' => $languageInput,
            'count' => $count,
            'matches' => $matchCount,
        ];
    }

}