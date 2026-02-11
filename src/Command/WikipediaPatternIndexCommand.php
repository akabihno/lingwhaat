<?php

namespace App\Command;

use App\Service\Search\WikipediaPatternIndexerService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:wikipedia-pattern-index',
    description: 'Index Wikipedia canonical patterns to Elasticsearch',
)]
class WikipediaPatternIndexCommand extends Command
{
    public function __construct(
        private readonly WikipediaPatternIndexerService $indexerService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'window-size',
            'w',
            InputOption::VALUE_REQUIRED,
            'Pattern window size to index'
        )
        ->addOption(
            'language-code',
            '',
            InputOption::VALUE_REQUIRED,
            'Language code'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $windowSize = (int) $input->getOption('window-size');
        $languageCode = (string) $input->getOption('language-code');

        $io->info("Indexing Wikipedia canonical patterns for $languageCode with window size $windowSize...");

        try {
            $this->indexerService->indexAllByLanguageCode($windowSize, $languageCode);

            $io->success("Indexed patterns to Elasticsearch!");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Indexing failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
