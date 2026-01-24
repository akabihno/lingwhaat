<?php

namespace App\Command;

use App\Service\Search\WikipediaPatternSearchService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:wikipedia-pattern-search',
    description: 'Search for patterns in Wikipedia articles',
)]
class WikipediaPatternSearchCommand extends Command
{
    public function __construct(
        private readonly WikipediaPatternSearchService $wikipediaPatternSearchService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('search-text', InputArgument::REQUIRED, 'Text to search for pattern matches')
            ->addOption('window-size', 'w', InputOption::VALUE_OPTIONAL, 'Pattern window size', 100)
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Maximum number of results', 50);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $searchText = $input->getArgument('search-text');
        $limit = (int) $input->getOption('limit');
        $windowSize = (int) $input->getOption('window-size');

        $io->title('Wikipedia Pattern Search');
        $io->writeln("Searching for pattern: <info>$searchText</info>");
        $io->writeln("Window size: <info>$windowSize</info>");

        try {
            $results = $this->wikipediaPatternSearchService->search($searchText, $limit, $windowSize);

            if (empty($results)) {
                $io->warning('No matching patterns found.');
                return Command::SUCCESS;
            }

            $io->success("Found " . count($results) . " matching patterns!");
            $io->newLine();

            foreach ($results as $result) {
                $io->section('Match');
                $io->writeln("Global Position: {$result['global_position']}");
                $io->writeln("Article ID: {$result['article_id']}");
                $io->writeln("Local Position: {$result['local_position']}");
                $io->writeln("Length: {$result['length']}");
                $io->writeln("Pattern Hash: {$result['pattern_hash']}");
                $io->newLine();
                $io->writeln("Pattern:");
                $io->writeln("<comment>{$result['pattern']}</comment>");
                $io->newLine();
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Search failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
