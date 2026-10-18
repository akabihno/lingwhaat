<?php

namespace App\Command;

use App\Service\Search\WordSearchIndexer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:word-search-rebuild',
    description: 'Rebuild the word_search tables (MySQL word search) from the pronunciation tables',
)]
class WordSearchRebuildCommand extends Command
{
    public function __construct(
        private readonly WordSearchIndexer $indexer,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this->addOption('language-code', 'l', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Only rebuild these languages (repeatable); default: all');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $tables = $this->indexer->getLanguageTables();

        /** @var list<string> $only */
        $only = $input->getOption('language-code');
        if ($only !== []) {
            $unknown = array_diff($only, array_keys($tables));
            if ($unknown !== []) {
                $io->error('Unknown language code(s): ' . implode(', ', $unknown));
                return Command::FAILURE;
            }
            $tables = array_intersect_key($tables, array_flip($only));
        }

        $totalEntries = 0;
        $totalTokens = 0;
        $start = microtime(true);
        foreach ($tables as $languageCode => $table) {
            $languageStart = microtime(true);
            $stats = $this->indexer->rebuildLanguage($languageCode, $table);
            $totalEntries += $stats['entries'];
            $totalTokens += $stats['tokens'];
            $io->writeln(sprintf('%-10s %9d entries %9d tokens  %6.1fs', $languageCode, $stats['entries'], $stats['tokens'], microtime(true) - $languageStart));
        }

        $io->success(sprintf('Rebuilt %d languages: %d entries, %d tokens in %.0fs', count($tables), $totalEntries, $totalTokens, microtime(true) - $start));

        return Command::SUCCESS;
    }
}
