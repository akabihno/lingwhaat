<?php

namespace App\Command;

use App\Repository\ManuscriptPatternMatchRepository;
use App\Repository\ManuscriptPatternMatchResultRepository;
use App\Service\Logging\ElasticsearchLogger;
use App\Service\Metrics\PrometheusMetricsService;
use App\Service\Stats\CanonicalPatternStatsService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:canonical-pattern-stats',
    description: 'Compute the canonical-pattern overlap between a language (Wikipedia) and each manuscript source_id, push to Prometheus.',
)]
class CanonicalPatternStatsCommand extends Command
{
    private const int DEFAULT_WINDOW_SIZE = 18;
    private const int DEFAULT_TOP_N = 50;

    private const string GAUGE_OVERLAP_WIKI = 'canonical_pattern_overlap_wikipedia_count';
    private const string GAUGE_OVERLAP_MANUSCRIPT = 'canonical_pattern_overlap_manuscript_count';

    public function __construct(
        private readonly CanonicalPatternStatsService $stats,
        private readonly ManuscriptPatternMatchRepository $manuscriptRepository,
        private readonly ManuscriptPatternMatchResultRepository $resultRepository,
        private readonly PrometheusMetricsService $metrics,
        private readonly ElasticsearchLogger $esLogger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('language-code', 'l', InputOption::VALUE_REQUIRED, 'Wikipedia language code to compare against (e.g. en, fr, ru).')
            ->addOption('window-size', 'w', InputOption::VALUE_OPTIONAL, 'Sliding window size in characters', self::DEFAULT_WINDOW_SIZE)
            ->addOption('top-n', 't', InputOption::VALUE_OPTIONAL, 'Top-N most common patterns to consider on each side', self::DEFAULT_TOP_N)
            ->addOption('max-counters', 'm', InputOption::VALUE_OPTIONAL, 'Misra-Gries counter cap (higher = more accurate, more RAM).', CanonicalPatternStatsService::DEFAULT_MAX_COUNTERS)
            ->addOption('article-limit', 'a', InputOption::VALUE_OPTIONAL, 'Cap on Wikipedia articles to process (smoke-test knob; 0 = all)', 0);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $languageCode = (string) $input->getOption('language-code');
        if ($languageCode === '') {
            $io->error('Option --language-code is required.');
            return Command::FAILURE;
        }

        $windowSize = (int) $input->getOption('window-size');
        if ($windowSize < 2) {
            $io->error('Window size must be at least 2.');
            return Command::FAILURE;
        }

        $topN = (int) $input->getOption('top-n');
        if ($topN < 1) {
            $io->error('--top-n must be >= 1.');
            return Command::FAILURE;
        }

        $maxCounters = (int) $input->getOption('max-counters');
        if ($maxCounters < $topN * 10) {
            $io->error(sprintf('--max-counters (%d) must be at least 10x --top-n (%d) for reliable heavy-hitter recovery.', $maxCounters, $topN));
            return Command::FAILURE;
        }

        $articleLimitOpt = (int) $input->getOption('article-limit');
        $articleLimit = $articleLimitOpt > 0 ? $articleLimitOpt : null;

        $started = microtime(true);

        // Each run fully replaces the overlap metrics — wipe stale series from previous runs first.
        $io->writeln('Wiping previous canonical-pattern metrics from Redis...');
        $this->metrics->wipe();

        $io->section(sprintf(
            'Wikipedia (language=%s, window=%d, top=%d, max_counters=%d, article_limit=%s)',
            $languageCode,
            $windowSize,
            $topN,
            $maxCounters,
            $articleLimit === null ? 'all' : (string) $articleLimit,
        ));

        $progress = function (int $processed, int $counters) use ($io, $started): void {
            $elapsed = max(microtime(true) - $started, 0.001);
            $io->writeln(sprintf(
                '  [%6.1fs] processed=%d counters=%d mem=%s',
                $elapsed,
                $processed,
                $counters,
                $this->formatBytes(memory_get_usage(true)),
            ));
        };

        $wikipediaTop = $this->stats->topPatternsForLanguage(
            $languageCode,
            $windowSize,
            $topN,
            $maxCounters,
            $articleLimit,
            $progress,
        );
        $io->writeln(sprintf('  -> top %d Wikipedia patterns collected', count($wikipediaTop)));

        $wikiCountByPattern = [];
        foreach ($wikipediaTop as $entry) {
            $wikiCountByPattern[$entry['pattern']] = (int) $entry['count'];
        }

        $sourceIds = $this->manuscriptRepository->findDistinctSourceIds();
        $io->section(sprintf('Manuscript sources: %d distinct source_ids', count($sourceIds)));

        $labelNames = ['language', 'source_id', 'window', 'pattern'];
        $wikiGauge = $this->metrics->gauge(
            self::GAUGE_OVERLAP_WIKI,
            'Wikipedia occurrence count for canonical patterns that are shared between language=$language top-N and the manuscript source_id top-N.',
            $labelNames,
        );
        $manuscriptGauge = $this->metrics->gauge(
            self::GAUGE_OVERLAP_MANUSCRIPT,
            'Manuscript occurrence count for canonical patterns that are shared between language=$language top-N and the manuscript source_id top-N.',
            $labelNames,
        );

        $totalSharedSeries = 0;
        $totalPersistedMatches = 0;
        foreach ($sourceIds as $sourceId) {
            $sourceStarted = microtime(true);
            $sourceProgress = function (int $processed, int $counters) use ($io, $sourceStarted, $sourceId): void {
                $elapsed = max(microtime(true) - $sourceStarted, 0.001);
                $io->writeln(sprintf(
                    '  [src=%d %5.1fs] rows=%d counters=%d',
                    $sourceId,
                    $elapsed,
                    $processed,
                    $counters,
                ));
            };

            $analysis = $this->stats->analyzeManuscriptSource(
                $sourceId,
                $windowSize,
                $topN,
                $maxCounters,
                $wikiCountByPattern,
                $sourceProgress,
            );
            $top = $analysis['top'];
            $overlapsByMatch = $analysis['overlapsByMatch'];

            // Final shared = manuscript top-N ∩ wiki top-N
            $sharedPatterns = [];
            foreach ($top as $entry) {
                $pattern = (string) $entry['pattern'];
                if (!isset($wikiCountByPattern[$pattern])) {
                    continue;
                }
                $sharedPatterns[$pattern] = (int) $entry['count'];

                $labels = [$languageCode, (string) $sourceId, (string) $windowSize, $pattern];
                $wikiGauge->set((float) $wikiCountByPattern[$pattern], $labels);
                $manuscriptGauge->set((float) $entry['count'], $labels);
            }

            $totalSharedSeries += count($sharedPatterns);

            // Persist each match that contains at least one final-shared pattern, then ES-log it.
            $persistedForSource = 0;
            foreach ($overlapsByMatch as $matchId => $matchOverlaps) {
                $filtered = [];
                foreach ($matchOverlaps as $overlap) {
                    $pattern = $overlap['pattern'];
                    if (!isset($sharedPatterns[$pattern])) {
                        continue;
                    }
                    $filtered[] = [
                        'pattern' => $pattern,
                        'position' => $overlap['position'],
                        'wiki_count' => $overlap['wiki_count'],
                        'manuscript_count' => $sharedPatterns[$pattern],
                    ];
                }
                if ($filtered === []) {
                    continue;
                }

                $payload = [
                    'detector' => 'canonical_pattern_overlap',
                    'language_code' => $languageCode,
                    'window_size' => $windowSize,
                    'top_n' => $topN,
                    'overlaps' => $filtered,
                ];
                $json = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

                // language_score is intentionally left NULL so the scheduled scoring job's
                // findUnscored() picks up this row. language_code is also left NULL since the
                // scorer determines and writes it; the language being compared is preserved
                // inside the results JSON under "language_code".
                $this->resultRepository->insert($matchId, $sourceId, $json);

                $this->esLogger->info('Canonical pattern overlap detected', [
                    'event' => 'canonical_pattern_overlap',
                    'match_id' => $matchId,
                    'source_id' => $sourceId,
                    'language_code' => $languageCode,
                    'window_size' => $windowSize,
                    'top_n' => $topN,
                    'shared_pattern_count' => count($filtered),
                    'patterns' => array_column($filtered, 'pattern'),
                ]);

                $persistedForSource++;
            }
            $totalPersistedMatches += $persistedForSource;

            $io->writeln(sprintf(
                '  source_id=%d -> %d shared patterns / %d match rows persisted (lang=%s top-%d)',
                $sourceId,
                count($sharedPatterns),
                $persistedForSource,
                $languageCode,
                $topN,
            ));
        }

        $io->success(sprintf(
            'language=%s: %d shared-pattern series, %d match-rows persisted, %d sources in %.1fs.',
            $languageCode,
            $totalSharedSeries,
            $totalPersistedMatches,
            count($sourceIds),
            microtime(true) - $started,
        ));

        return Command::SUCCESS;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KiB', 'MiB', 'GiB'];
        $i = 0;
        $value = (float) $bytes;
        while ($value >= 1024 && $i < count($units) - 1) {
            $value /= 1024;
            $i++;
        }
        return sprintf('%.1f%s', $value, $units[$i]);
    }
}
