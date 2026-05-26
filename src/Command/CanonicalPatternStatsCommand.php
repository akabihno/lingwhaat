<?php

namespace App\Command;

use App\Repository\ManuscriptPatternMatchRepository;
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
    description: 'Compute top canonical patterns for a language (Wikipedia) and each manuscript source_id, push counts to Prometheus.',
)]
class CanonicalPatternStatsCommand extends Command
{
    private const int DEFAULT_WINDOW_SIZE = 18;
    private const int DEFAULT_TOP_N = 50;

    private const string GAUGE_WIKIPEDIA = 'canonical_pattern_wikipedia_count';
    private const string GAUGE_MANUSCRIPT = 'canonical_pattern_manuscript_count';

    public function __construct(
        private readonly CanonicalPatternStatsService $stats,
        private readonly ManuscriptPatternMatchRepository $manuscriptRepository,
        private readonly PrometheusMetricsService $metrics,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'language-code',
                'l',
                InputOption::VALUE_REQUIRED,
                'Target language code (e.g. en, fr, ru) for the Wikipedia side.',
            )
            ->addOption(
                'window-size',
                'w',
                InputOption::VALUE_OPTIONAL,
                'Sliding window size in characters',
                self::DEFAULT_WINDOW_SIZE,
            )
            ->addOption(
                'top-n',
                't',
                InputOption::VALUE_OPTIONAL,
                'How many top patterns to record per series',
                self::DEFAULT_TOP_N,
            )
            ->addOption(
                'max-counters',
                'm',
                InputOption::VALUE_OPTIONAL,
                'Misra-Gries counter cap. Higher = more accurate top-N counts, more RAM. Default keeps memory well under 256 MiB.',
                CanonicalPatternStatsService::DEFAULT_MAX_COUNTERS,
            )
            ->addOption(
                'article-limit',
                'a',
                InputOption::VALUE_OPTIONAL,
                'Cap on Wikipedia articles to process (smoke-test knob; omit or 0 to process everything).',
                0,
            );
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
        $io->writeln(sprintf('  -> top %d patterns collected', count($wikipediaTop)));
        $this->pushPriorityGauge(
            self::GAUGE_WIKIPEDIA,
            'Counts of the top-N canonical patterns observed in Wikipedia articles for a language. priority=N is the most common.',
            ['language', 'window', 'priority'],
            ['language' => $languageCode, 'window' => (string) $windowSize],
            $wikipediaTop,
        );
        $io->writeln('  -> Wikipedia metrics written to Prometheus storage');

        $sourceIds = $this->manuscriptRepository->findDistinctSourceIds();
        $io->section(sprintf('Manuscript sources: %d distinct source_ids', count($sourceIds)));

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
            $top = $this->stats->topPatternsForManuscriptSource(
                $sourceId,
                $windowSize,
                $topN,
                $maxCounters,
                $sourceProgress,
            );
            $io->writeln(sprintf('  source_id=%d -> %d patterns', $sourceId, count($top)));
            $this->pushPriorityGauge(
                self::GAUGE_MANUSCRIPT,
                'Counts of the top-N canonical patterns observed in manuscript_pattern_match.source_data per source_id. priority=N is the most common.',
                ['source_id', 'window', 'priority'],
                ['source_id' => (string) $sourceId, 'window' => (string) $windowSize],
                $top,
            );
        }

        $io->success(sprintf(
            'Wrote %d Wikipedia + %d manuscript series to Prometheus storage in %.1fs.',
            count($wikipediaTop),
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

    /**
     * @param array<string>                                 $labelNames Including 'priority' as the last label.
     * @param array<string,string>                          $baseLabels Common labels for this series (e.g. language=en).
     * @param array<int, array{pattern:string, count:int}>  $top
     */
    private function pushPriorityGauge(
        string $metric,
        string $help,
        array $labelNames,
        array $baseLabels,
        array $top,
    ): void {
        $gauge = $this->metrics->gauge($metric, $help, $labelNames);
        $totalRanks = count($top);

        foreach ($top as $rank => $entry) {
            // rank 0 = most common in $top => priority = totalRanks; last item => priority = 1
            $priority = $totalRanks - $rank;

            $labels = $baseLabels;
            $labels['priority'] = (string) $priority;

            $ordered = [];
            foreach ($labelNames as $name) {
                $ordered[] = (string) ($labels[$name] ?? '');
            }

            $gauge->set((float) $entry['count'], $ordered);
        }
    }
}
