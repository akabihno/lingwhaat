<?php

namespace App\Command;

use App\Service\WiktionaryArticlesIpaParserService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:parse-wiktionary-articles',
    description: 'Parse IPA data from Wiktionary for a specific language',
)]
class ParseWiktionaryArticlesCommand extends Command
{
    public function __construct(
        private readonly WiktionaryArticlesIpaParserService $parserService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('language', InputArgument::REQUIRED, 'Language to parse (e.g., dutch, icelandic)')
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Limit number of articles to process', 100)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $language = strtolower($input->getArgument('language'));
        $limit = (int) $input->getOption('limit');

        $io->info(sprintf('Starting to parse Wiktionary articles for language: %s (limit: %d)', $language, $limit));

        try {
            $this->parserService->run($language, $limit);
            $io->success('Parsing completed successfully.');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Parsing failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
