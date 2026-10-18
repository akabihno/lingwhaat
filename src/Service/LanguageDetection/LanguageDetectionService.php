<?php

namespace App\Service\LanguageDetection;

use App\Constant\WordSearchBackend;
use App\Service\LanguageNormalizationService;
use App\Service\Logging\ElasticsearchLogger;
use App\Service\Search\FuzzySearchService;
use App\Service\Search\WordSearchShadow;
use Symfony\Component\Uid\Uuid;

class LanguageDetectionService
{
    const int EXACT_MATCH_SCORE = 5;
    public function __construct(
        protected ElasticsearchLogger $logger,
        protected LanguageNormalizationService $languageNormalizationService,
        protected FuzzySearchService $fuzzySearchService,
        private readonly WordSearchShadow $shadow,
        private readonly string $wordLookupBackend = WordSearchBackend::ELASTICSEARCH,
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

        // Shadow mode: tally the votes MySQL's lookups would have cast, from the results the shadow
        // router already computed, to compare whole detections (not just per-word result lists).
        $shadowing = $this->wordLookupBackend === WordSearchBackend::SHADOW;
        $mysqlCounts = [];
        $mysqlComplete = true;
        // A plain closure on purpose: an arrow function would capture the tallies by value.
        $mysqlVotes = function (int $weight) use ($shadowing, &$mysqlCounts, &$mysqlComplete): ?callable {
            if (!$shadowing) {
                return null;
            }

            return function (?array $matches) use (&$mysqlCounts, &$mysqlComplete, $weight): void {
                if ($matches === null) {
                    $mysqlComplete = false;
                    return;
                }
                self::addVotes($mysqlCounts, $matches, $weight);
            };
        };

        foreach ($words as $word) {
            $word = $this->languageNormalizationService->normalizeWord($word);
            if (!$word) {
                continue;
            }

            try {
                $exactMatches = $this->fuzzySearchService->findExactMatches($word, null, $mysqlVotes(self::EXACT_MATCH_SCORE));
                $matchCount += self::addVotes($languageCounts, $exactMatches, self::EXACT_MATCH_SCORE);

                $matches = $this->fuzzySearchService->findClosestMatches($word, 5, '', $mysqlVotes(1));
                $matchCount += self::addVotes($languageCounts, $matches, 1);
            } catch (\Throwable $e) {
                // The two tallies no longer cover the same lookups.
                $mysqlComplete = false;
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

        if ($shadowing) {
            $this->compareWithMysql($uuid, $languageInput, $languageCounts, $mysqlCounts, $mysqlComplete);
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

    /**
     * Record whether MySQL's lookups would have detected the same language. A different winner
     * that MySQL scores equal to ES's winner is a "tie": which of equal counts wins depends only on
     * result order.
     *
     * @param array<string, int> $esCounts
     * @param array<string, int> $mysqlCounts
     */
    private function compareWithMysql(string $uuid, string $input, array $esCounts, array $mysqlCounts, bool $complete): void
    {
        $esTop = self::topLanguage($esCounts);
        $mysqlTop = self::topLanguage($mysqlCounts);

        $outcome = match (true) {
            !$complete => 'incomplete',
            $esTop === $mysqlTop => 'same',
            $esTop !== null && $mysqlTop !== null && ($mysqlCounts[$esTop] ?? 0) === $mysqlCounts[$mysqlTop] => 'tie',
            default => 'different',
        };

        arsort($esCounts);
        arsort($mysqlCounts);
        $this->shadow->recordComparison(WordSearchShadow::SHAPE_DETECTED_LANGUAGE, $outcome, [
            'uuid' => $uuid,
            'input' => $input,
            'es' => array_slice($esCounts, 0, 5, true),
            'mysql' => array_slice($mysqlCounts, 0, 5, true),
        ]);
    }

}