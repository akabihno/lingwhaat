<?php

namespace App\Service\Search;

use App\Repository\ManuscriptCharacterGroupRepository;
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
    // How many windows to bundle into a single _msearch round-trip. Kept conservative because the
    // corpus ES is a single node: _msearch runs its sub-searches concurrently against the search
    // threadpool, so an over-large batch is exactly the "No alive nodes" saturation risk. It also
    // bounds the wasted work when the MAX_TOTAL_HITS early-exit fires mid-batch (a full chunk is
    // always issued). Tune upward while watching ES rather than starting high.
    private const int WINDOWS_PER_MSEARCH = 25;

    public function __construct(
        private readonly ManuscriptPatternMatchScheduleRepository $scheduleRepository,
        private readonly ManuscriptPatternMatchRepository $matchRepository,
        private readonly ManuscriptPatternMatchResultRepository $resultRepository,
        private readonly WikipediaPatternSearchService $searchService,
        private readonly ManuscriptCharacterGroupRepository $characterGroupRepository,
        private readonly ElasticsearchLogger $logger,
        // Feature flag (env MANUSCRIPT_CHARACTER_GROUPING_ENABLED). When on, configured multi-char
        // sequences for a source collapse to a single glyph before the canonical pattern is built;
        // when off the tokenizer degrades to a per-character split (legacy behaviour).
        private readonly bool $characterGroupingEnabled = false,
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

            // Grouping sequences are per source, and every match here shares this schedule's id as
            // its sourceId (findBySourceId), so resolve them once for the whole schedule instead of
            // re-issuing the same query per match.
            $sequences = $this->characterGroupingEnabled
                ? $this->characterGroupRepository->findSequencesBySourceId($schedule->getId())
                : [];

            foreach ($matches as $match) {
                $normalized = $this->normalize($match->getSourceData());
                $windowSize = WikipediaPatternSearchService::DEFAULT_WINDOW_SIZE;

                // Tokenize into glyphs. With grouping enabled, sequences configured for this source
                // collapse to a single token; otherwise every character is its own token (legacy).
                // A window is $windowSize *tokens*, so a grouped window can span more than
                // $windowSize characters while still producing a $windowSize-length canonical
                // pattern — the length the per-character corpus index was built with.
                $tokens = ManuscriptWindowTokenizer::tokenize($normalized, $sequences);
                $tokenCount = count($tokens);

                if ($tokenCount < $windowSize) {
                    $this->logger->info(sprintf('Skipping match id=%d: token length %d < window size %d', $match->getId(), $tokenCount, $windowSize), [
                        'service' => self::LOG_SERVICE,
                    ]);
                    continue;
                }

                // Build every window once (keyed by token position), then search them against the
                // corpus in batched _msearch round-trips instead of one ES request per window.
                $windows = [];
                for ($pos = 0; $pos <= $tokenCount - $windowSize; $pos++) {
                    $windows[$pos] = array_slice($tokens, $pos, $windowSize);
                }

                $allHits = [];
                $windowCount = count($windows);

                foreach (array_chunk($windows, self::WINDOWS_PER_MSEARCH, true) as $windowChunk) {
                    $hitsByPos = $this->searchService->searchByPatterns($windowChunk, self::RESULTS_PER_WINDOW, $languageCode);

                    foreach ($hitsByPos as $pos => $windowHits) {
                        // Recorded as-written: cipher_window is the concatenated glyphs (a grouped
                        // token contributes all its characters) and cipher_position is the token index.
                        $window = implode('', $windows[$pos]);
                        foreach ($windowHits as $hit) {
                            $hit['cipher_position'] = $pos;
                            $hit['cipher_window'] = $window;
                            $allHits[] = $hit;
                        }

                        // Cap total hits per match — this whole array becomes one JSON result row.
                        // Checked per window (not per chunk) so the blob overshoots 400 by at most
                        // one window's worth of hits, as it did before batching.
                        if (count($allHits) >= self::MAX_TOTAL_HITS) {
                            break 2;
                        }
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
