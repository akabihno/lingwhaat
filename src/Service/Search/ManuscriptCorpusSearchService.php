<?php

namespace App\Service\Search;

use App\Repository\ManuscriptPatternMatchRepository;
use App\Repository\ManuscriptPatternMatchResultRepository;
use App\Repository\ManuscriptPatternMatchScheduleRepository;
use App\Service\Logging\ElasticsearchLogger;

/**
 * Runs every manuscript schedule's matches against whatever is currently resident in the per-language
 * corpus index(es) and appends the hits to manuscript_pattern_match_result.
 *
 * In the eviction model the corpus index is scratch space holding only the in-flight batch: the
 * pattern-index handler indexes a batch, calls this to search it, then evicts it. Because results are
 * appended (not upserted), coverage accumulates across batches; because each call only sees the
 * current batch, every region of the corpus is searched exactly once per pass instead of the old
 * whole-index sweep that only ever re-reported the lowest article ids.
 */
class ManuscriptCorpusSearchService
{
    private const string LOG_SERVICE = '[ManuscriptCorpusSearchService]';
    private const int RESULTS_PER_WINDOW = 5;
    private const int MAX_TOTAL_HITS = 400;

    public function __construct(
        private readonly ManuscriptPatternMatchScheduleRepository $scheduleRepository,
        private readonly ManuscriptPatternMatchRepository $matchRepository,
        private readonly ManuscriptPatternMatchResultRepository $resultRepository,
        private readonly WikipediaPatternSearchService $searchService,
        private readonly ElasticsearchLogger $logger,
    ) {
    }

    public function searchLanguage(?string $languageCode): void
    {
        $schedules = $this->scheduleRepository->getAll();
        $this->logger->info(sprintf('Processing %d manuscript schedules against language=%s', count($schedules), $languageCode ?? 'all'), [
            'service' => self::LOG_SERVICE,
            'languageCode' => $languageCode,
        ]);

        foreach ($schedules as $schedule) {
            $matches = $this->matchRepository->findBySourceId($schedule->getId());
            $this->logger->info(sprintf('Schedule "%s" (id: %d): found %d matches', $schedule->getManuscriptName(), $schedule->getId(), count($matches)), [
                'service' => self::LOG_SERVICE,
            ]);

            foreach ($matches as $match) {
                $normalized = $this->normalize($match->getSourceData());
                $textLength = mb_strlen($normalized);
                $windowSize = WikipediaPatternSearchService::DEFAULT_WINDOW_SIZE;

                if ($textLength < $windowSize) {
                    $this->logger->info(sprintf('Skipping match id=%d: normalized length %d < window size %d', $match->getId(), $textLength, $windowSize), [
                        'service' => self::LOG_SERVICE,
                    ]);
                    continue;
                }

                $allHits = [];
                $windowCount = $textLength - $windowSize + 1;

                for ($pos = 0; $pos <= $textLength - $windowSize; $pos++) {
                    $window = mb_substr($normalized, $pos, $windowSize);

                    try {
                        $windowHits = $this->searchService->search($window, self::RESULTS_PER_WINDOW, WikipediaPatternSearchService::DEFAULT_WINDOW_SIZE, $languageCode);
                    } catch (\InvalidArgumentException) {
                        continue;
                    }

                    foreach ($windowHits as $hit) {
                        $hit['cipher_position'] = $pos;
                        $hit['cipher_window'] = $window;
                        $allHits[] = $hit;
                    }

                    if (count($allHits) >= self::MAX_TOTAL_HITS) {
                        break;
                    }
                }

                $this->logger->info(sprintf('Match id=%d: found %d hits across %d windows', $match->getId(), count($allHits), $windowCount), [
                    'service' => self::LOG_SERVICE,
                ]);

                if (empty($allHits)) {
                    continue;
                }

                try {
                    $this->resultRepository->insert($match->getId(), $schedule->getId(), json_encode($allHits, JSON_THROW_ON_ERROR));
                    $this->logger->info(sprintf('Match id=%d: insert complete', $match->getId()), ['service' => self::LOG_SERVICE]);
                } catch (\Throwable $e) {
                    $this->logger->error(sprintf('Match id=%d: insert failed: %s', $match->getId(), $e->getMessage()), ['service' => self::LOG_SERVICE]);
                }
            }
        }

        $this->logger->info('Manuscript pattern search complete', ['service' => self::LOG_SERVICE]);
    }

    private function normalize(string $s): string
    {
        $s = mb_strtolower($s);
        return preg_replace('/[^\p{L}]+/u', '', $s) ?? '';
    }
}
