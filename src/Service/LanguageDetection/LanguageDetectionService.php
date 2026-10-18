<?php

namespace App\Service\LanguageDetection;

use App\Service\LanguageNormalizationService;
use App\Service\Logging\PipelineLogger;
use App\Service\Search\FuzzySearchService;
use Symfony\Component\Uid\Uuid;

class LanguageDetectionService
{
    const int EXACT_MATCH_SCORE = 5;
    public function __construct(
        protected PipelineLogger $logger,
        protected LanguageNormalizationService $languageNormalizationService,
        protected FuzzySearchService $fuzzySearchService,
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
                $matchCount += self::addVotes($languageCounts, $exactMatches, self::EXACT_MATCH_SCORE);

                $matches = $this->fuzzySearchService->findClosestMatches($word);
                $matchCount += self::addVotes($languageCounts, $matches, 1);
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

        $topLanguageCode = self::topLanguage($languageCounts);

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

    /**
     * Add one vote of $weight per match to its language. Returns the number of votes cast.
     *
     * @param array<string, int> $counts
     * @param list<array<string, mixed>> $matches
     */
    private static function addVotes(array &$counts, array $matches, int $weight): int
    {
        $votes = 0;
        foreach ($matches as $match) {
            if (!isset($match['languageCode'])) {
                continue;
            }

            $code = $match['languageCode'];
            $counts[$code] = ($counts[$code] ?? 0) + $weight;
            $votes++;
        }

        return $votes;
    }

    /**
     * @param array<string, int> $counts
     */
    private static function topLanguage(array $counts): ?string
    {
        if ($counts === []) {
            return null;
        }
        arsort($counts);

        return array_key_first($counts);
    }

}