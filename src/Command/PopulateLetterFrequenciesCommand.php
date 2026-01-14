<?php

namespace App\Command;

use App\Service\LetterFrequencyService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:populate-letter-frequencies',
    description: 'Calculate and populate letter frequencies for all languages or a specific language',
)]
class PopulateLetterFrequenciesCommand extends Command
{
    public function __construct(
        private LetterFrequencyService $letterFrequencyService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('language', InputArgument::OPTIONAL, 'Specific language code to process (e.g., ru, en)')
            ->addOption('create-table', null, InputOption::VALUE_NONE, 'Create the letter_frequency table if it does not exist')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $languageCode = $input->getArgument('language');
        $createTable = $input->getOption('create-table');

        try {
            // Create table if requested
            if ($createTable) {
                $io->info('Creating letter_frequency table...');
                $this->letterFrequencyService->createTable();
                $io->success('Table created successfully.');
            }

            // Process specific language or all languages
            if ($languageCode) {
                $io->info("Calculating letter frequencies for language: {$languageCode}");
                $letterCount = $this->letterFrequencyService->calculateAndStoreFrequencies($languageCode);

                if ($letterCount > 0) {
                    $io->success("Processed {$languageCode}: {$letterCount} letters");
                } else {
                    $io->warning("No letters found for {$languageCode}");
                }
            } else {
                $io->info('Calculating letter frequencies for all languages...');
                $results = $this->letterFrequencyService->calculateAndStoreAllLanguages();

                // Display results
                $tableRows = [];
                $totalLetters = 0;
                foreach ($results as $lang => $count) {
                    $tableRows[] = [$lang, $count];
                    $totalLetters += $count;
                }

                $io->table(['Language Code', 'Letter Count'], $tableRows);
                $io->success("Processed " . count($results) . " languages with {$totalLetters} total letter entries");
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('An error occurred: ' . $e->getMessage());
            $io->text('Stack trace: ' . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}
