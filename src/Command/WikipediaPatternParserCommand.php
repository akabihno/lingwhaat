<?php

namespace App\Command;

use App\Service\WikipediaPatternParserService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:wikipedia-pattern-parser',
    description: 'Parse data from Wikipedia for a specific language to create letter patterns',
)]
class WikipediaPatternParserCommand extends Command
{
    public function __construct(
        private readonly WikipediaPatternParserService $parserService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('languageCode', InputArgument::REQUIRED, 'Wikipedia language code to parse articles from (e.g. en, nl)')
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Limit number of articles to process', 100)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $languageCode = strtolower($input->getArgument('languageCode'));
        $limit = (int) $input->getOption('limit');

        $io->info(sprintf('Starting to parse Wikipedia articles for language: %s (limit: %d)', $languageCode, $limit));

        try {
            $this->parserService->run($languageCode, $limit);
            $io->success('Parsing completed successfully.');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Parsing failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

}