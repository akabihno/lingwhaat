<?php

namespace App\Service\Search;

use App\Repository\ManuscriptCharacterGroupRepository;
use App\Repository\ManuscriptPatternMatchRepository;
use App\Repository\ManuscriptPatternMatchResultRepository;
use App\Repository\ManuscriptPatternMatchScheduleRepository;
use App\Service\Logging\ElasticsearchLogger;

/**
 * Manuscript side of the corpus pattern search. For each corpus batch the pattern-index handler asks
 * this service for a {@see ManuscriptWindowMatcher} loaded with every manuscript window, streams the
 * batch through it ({@see WikipediaCorpusScanner}), then hands it back to saveResults(), which
 * appends the hits to manuscript_pattern_match_result.
 *
 * Results are appended (not upserted), so coverage accumulates across batches; because each matcher
 * only ever sees one batch, every region of the corpus is searched exactly once per pass rather than
 * forever re-reporting the lowest article ids.
 */
class ManuscriptCorpusSearchService
{
    private const string LOG_SERVICE = '[ManuscriptCorpusSearchService]';
    private const int RESULTS_PER_WINDOW = 5;
    private const int MAX_TOTAL_HITS = 400;
    // Rolling hash of the canonical pattern, still written into each hit's pattern_hash for
    // continuity with result rows produced by the former Elasticsearch search.
    private const int BASE = 101;
    private const int MOD = 1000000007;

    public function __construct(
        private readonly ManuscriptPatternMatchScheduleRepository $scheduleRepository,
        private readonly ManuscriptPatternMatchRepository $matchRepository,
        private readonly ManuscriptPatternMatchResultRepository $resultRepository,
        private readonly ManuscriptCharacterGroupRepository $characterGroupRepository,
        private readonly ElasticsearchLogger $logger,
        // Feature flag (env MANUSCRIPT_CHARACTER_GROUPING_ENABLED). When on, configured multi-char
        // sequences for a source collapse to a single glyph before the canonical pattern is built;
        // when off the tokenizer degrades to a per-character split (legacy behaviour).
        private readonly bool $characterGroupingEnabled = false,
    ) {
    }

    /**
     * Load every schedule's matches as $windowSize-token windows into a fresh matcher.
     */
    public function buildMatcher(int $windowSize): ManuscriptWindowMatcher
    {
        $matcher = new ManuscriptWindowMatcher($windowSize, self::RESULTS_PER_WINDOW);

        foreach ($this->scheduleRepository->getAll() as $schedule) {
            $matches = $this->matchRepository->findBySourceId($schedule->getId());

            // Grouping sequences are per source, and every match here shares this schedule's id as
            // its sourceId (findBySourceId), so resolve them once for the whole schedule instead of
            // re-issuing the same query per match.
            $sequences = $this->characterGroupingEnabled
                ? $this->characterGroupRepository->findSequencesBySourceId($schedule->getId())
                : [];

            foreach ($matches as $match) {
                // Tokenize into glyphs. With grouping enabled, sequences configured for this source
                // collapse to a single token; otherwise every character is its own token (legacy).
                // A window is $windowSize *tokens*, so a grouped window can span more than
                // $windowSize characters while still producing a $windowSize-length canonical
                // pattern — the length of the per-character corpus windows.
                $tokens = ManuscriptWindowTokenizer::tokenize($this->normalize($match->getSourceData()), $sequences);
                $tokenCount = count($tokens);

                if ($tokenCount < $windowSize) {
                    $this->logger->info(sprintf('Skipping match id=%d: token length %d < window size %d', $match->getId(), $tokenCount, $windowSize), [
                        'service' => self::LOG_SERVICE,
                    ]);
                    continue;
                }

                for ($pos = 0; $pos <= $tokenCount - $windowSize; $pos++) {
                    $matcher->addWindow($match->getId(), $schedule->getId(), $pos, array_slice($tokens, $pos, $windowSize));
                }
            }
        }

        return $matcher;
    }

    /**
     * Append one result row per match that got hits in this batch. Within a match, hits are taken
     * window by window in cipher order (each window already capped at RESULTS_PER_WINDOW), and the
     * match stops at MAX_TOTAL_HITS since the whole array becomes one JSON result row.
     */
    public function saveResults(ManuscriptWindowMatcher $matcher, string $languageCode): void
    {
        foreach ($matcher->getResultsByMatch() as $matchId => $windows) {
            $allHits = [];
            $sourceId = $windows[0]['sourceId'];

            foreach ($windows as $window) {
                foreach ($window['hits'] as [$articleId, $localPosition]) {
                    // Recorded as-written: cipher_window is the concatenated glyphs (a grouped
                    // token contributes all its characters) and cipher_position is the token index.
                    $allHits[] = [
                        'article_id'      => $articleId,
                        'local_position'  => $localPosition,
                        'pattern'         => $window['pattern'],
                        'length'          => $matcher->getWindowSize(),
                        'pattern_hash'    => $this->patternHash($window['pattern']),
                        'language_code'   => $languageCode,
                        'cipher_position' => $window['position'],
                        'cipher_window'   => $window['window'],
                    ];
                }

                // Checked per window (not per hit) so the blob overshoots MAX_TOTAL_HITS by at most
                // one window's worth of hits.
                if (count($allHits) >= self::MAX_TOTAL_HITS) {
                    break;
                }
            }

            $this->logger->info(sprintf('Match id=%d: found %d hits across %d windows in %s batch', $matchId, count($allHits), count($windows), $languageCode), [
                'service' => self::LOG_SERVICE,
            ]);

            if ($allHits === []) {
                continue;
            }

            try {
                $this->resultRepository->insert($matchId, $sourceId, json_encode($allHits, JSON_THROW_ON_ERROR));
            } catch (\Throwable $e) {
                $this->logger->error(sprintf('Match id=%d: insert failed: %s', $matchId, $e->getMessage()), ['service' => self::LOG_SERVICE]);
            }
        }
    }

    private function patternHash(string $pattern): int
    {
        $hash = 0;
        foreach (explode(',', $pattern) as $value) {
            $hash = (($hash * self::BASE) + (int) $value) % self::MOD;
        }

        return $hash;
    }

    private function normalize(string $s): string
    {
        $s = mb_strtolower($s);
        return preg_replace('/[^\p{L}]+/u', '', $s) ?? '';
    }
}
