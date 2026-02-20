<?php

namespace App\Command;

use App\Service\Search\WordsPopularityScoreSetService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'language:set-popularity-score',
    description: 'Set popularity score for words in specific language',
)]
class WordsPopularityScoreSetCommand extends Command
{
    public function __construct(
        private readonly WordsPopularityScoreSetService $wordsPopularityScoreSetService
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('language', InputArgument::REQUIRED, 'Specific language code to process (e.g., ru, en)')
        ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Limit number of articles to process', 100)
        ->addOption('offset', 'o', InputOption::VALUE_OPTIONAL, 'Offset for articles to process', 0);

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $languageCode = $input->getArgument('language');
        $limit = (int) $input->getOption('limit');
        $offset = (int) $input->getOption('offset');

        if (!$languageCode) {
            $io->error('Language code is required.');
            return Command::FAILURE;
        }

        try {
            $this->wordsPopularityScoreSetService->execute($languageCode, $limit, $offset);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('An error occurred: ' . $e->getMessage());
            $io->text('Stack trace: ' . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }


}