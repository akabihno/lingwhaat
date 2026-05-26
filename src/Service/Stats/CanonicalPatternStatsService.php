<?php

namespace App\Service\Stats;

use App\Entity\WikipediaArticleEntity;
use App\Repository\ManuscriptPatternMatchRepository;
use App\Repository\WikipediaArticleRepository;
use Doctrine\ORM\EntityManagerInterface;

class CanonicalPatternStatsService
{
    private const int ARTICLE_FETCH_BATCH_SIZE = 500;
    private const int MANUSCRIPT_FETCH_BATCH_SIZE = 500;

    public const int DEFAULT_MAX_COUNTERS = 50_000;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ManuscriptPatternMatchRepository $manuscriptRepository,
    ) {
    }

    /**
     * Stream all Wikipedia articles for a language, count canonical patterns of the given window size,
     * return the top-N descending by count. Uses a bounded counter set (Misra-Gries) so memory stays
     * proportional to $maxCounters regardless of corpus size; any pattern occurring more often than
     * (stream_length / ($maxCounters + 1)) is guaranteed to be in the result, which comfortably covers
     * the top-50 heavy hitters for any natural language.
     *
     * @return array<int, array{pattern:string, count:int}>
     */
    public function topPatternsForLanguage(
        string $languageCode,
        int $windowSize,
        int $topN,
        int $maxCounters = self::DEFAULT_MAX_COUNTERS,
        ?int $articleLimit = null,
        ?\Closure $onProgress = null,
    ): array {
        /** @var WikipediaArticleRepository $repo */
        $repo = $this->em->getRepository(WikipediaArticleEntity::class);

        $counts = [];
        $offset = 0;
        $totalProcessed = 0;

        do {
            $fetchLimit = self::ARTICLE_FETCH_BATCH_SIZE;
            if ($articleLimit !== null) {
                $remaining = $articleLimit - $totalProcessed;
                if ($remaining <= 0) {
                    break;
                }
                $fetchLimit = min($fetchLimit, $remaining);
            }

            $articles = $repo->findIdAndTextByLanguageCodePaginated(
                $languageCode,
                $fetchLimit,
                $offset,
            );

            foreach ($articles as $article) {
                $this->accumulateCounts($article['text'], $windowSize, $counts, $maxCounters);
            }

            $offset += count($articles);
            $totalProcessed += count($articles);
            $this->em->clear();

            if ($onProgress !== null) {
                $onProgress($totalProcessed, count($counts));
            }
        } while ($articles !== []);

        return $this->topN($counts, $topN);
    }

    /**
     * Stream all manuscript_pattern_match rows for a source_id, count canonical patterns of the given
     * window size, return the top-N descending by count.
     *
     * @return array<int, array{pattern:string, count:int}>
     */
    public function topPatternsForManuscriptSource(
        int $sourceId,
        int $windowSize,
        int $topN,
        int $maxCounters = self::DEFAULT_MAX_COUNTERS,
        ?\Closure $onProgress = null,
    ): array {
        $counts = [];
        $offset = 0;
        $totalProcessed = 0;

        do {
            $rows = $this->manuscriptRepository->findSourceDataBySourceIdPaginated(
                $sourceId,
                self::MANUSCRIPT_FETCH_BATCH_SIZE,
                $offset,
            );

            foreach ($rows as $row) {
                $this->accumulateCounts($row['sourceData'], $windowSize, $counts, $maxCounters);
            }

            $offset += count($rows);
            $totalProcessed += count($rows);
            $this->em->clear();

            if ($onProgress !== null) {
                $onProgress($totalProcessed, count($counts));
            }
        } while ($rows !== []);

        return $this->topN($counts, $topN);
    }

    /**
     * @param array<string,int> $counts
     */
    private function accumulateCounts(string $text, int $windowSize, array &$counts, int $maxCounters): void
    {
        $normalized = $this->normalize($text);
        $chars = preg_split('//u', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $charCount = count($chars);

        if ($charCount < $windowSize) {
            return;
        }

        $lastWindowStart = $charCount - $windowSize;
        for ($start = 0; $start <= $lastWindowStart; $start++) {
            $window = array_slice($chars, $start, $windowSize);
            $pattern = $this->buildPattern($window);

            if (isset($counts[$pattern])) {
                $counts[$pattern]++;
                continue;
            }

            if (count($counts) < $maxCounters) {
                $counts[$pattern] = 1;
                continue;
            }

            // Misra-Gries decrement step: when the counter set is full, subtract 1 from every
            // counter and drop the zeros. The new pattern is effectively "consumed" by this
            // decrement and not stored. Heavy hitters survive, transient noise is evicted.
            foreach ($counts as $key => $value) {
                if ($value <= 1) {
                    unset($counts[$key]);
                } else {
                    $counts[$key] = $value - 1;
                }
            }
        }
    }

    /**
     * @param array<int,string> $chars
     */
    private function buildPattern(array $chars): string
    {
        $map = [];
        $nextId = 0;
        $pattern = [];

        foreach ($chars as $char) {
            if (!isset($map[$char])) {
                $map[$char] = $nextId++;
            }
            $pattern[] = $map[$char];
        }

        return implode(',', $pattern);
    }

    /**
     * @param array<string,int> $counts
     * @return array<int, array{pattern:string, count:int}>
     */
    private function topN(array $counts, int $topN): array
    {
        arsort($counts, SORT_NUMERIC);
        $top = array_slice($counts, 0, $topN, true);

        $result = [];
        foreach ($top as $pattern => $count) {
            $result[] = ['pattern' => $pattern, 'count' => $count];
        }

        return $result;
    }

    private function normalize(string $s): string
    {
        $s = mb_strtolower($s);
        return preg_replace('/[^\p{L}]+/u', '', $s) ?? '';
    }
}
