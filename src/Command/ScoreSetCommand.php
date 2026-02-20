<?php

namespace App\Command;

use App\Service\Search\ScoreSetService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ScoreSetCommand extends Command
{
    public function __construct(
        private readonly ScoreSetService $scoreSetService
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('language', InputArgument::REQUIRED, 'Specific language code to process (e.g., ru, en)');

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $languageCode = $input->getArgument('language');

        if (!$languageCode) {
            $io->error('Language code is required.');
            return Command::FAILURE;
        }

        try {


            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('An error occurred: ' . $e->getMessage());
            $io->text('Stack trace: ' . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }


}