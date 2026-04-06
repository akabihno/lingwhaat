<?php

namespace App\Command;

use App\Repository\WordCategoryRepository;
use App\Service\Search\WordCategoryIndexer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:index-word-categories',
    description: 'Index word category vectors into Elasticsearch for semantic similarity search',
)]
class IndexWordCategoriesCommand extends Command
{
    public function __construct(
        private readonly WordCategoryIndexer $indexer,
        private readonly WordCategoryRepository $repository,
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
                'Index only a specific language (e.g. en, de). Omit to index all languages.'
            )
            ->addOption(
                'recreate',
                null,
                InputOption::VALUE_NONE,
                'Drop and recreate the Elasticsearch index before indexing'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $languageCode = $input->getOption('language-code');
        $recreate = (bool) $input->getOption('recreate');

        if ($recreate) {
            $io->info('Dropping and recreating the Elasticsearch index…');
            try {
                $this->indexer->createIndex(dropExisting: true);
                $io->success('Index recreated.');
            } catch (\Throwable $e) {
                $io->error('Failed to recreate index: ' . $e->getMessage());
                return Command::FAILURE;
            }
        } else {
            $io->info('Ensuring index exists (will not drop existing data)…');
            try {
                $this->indexer->createIndex(dropExisting: false);
            } catch (\Throwable $e) {
                $io->error('Failed to create/verify index: ' . $e->getMessage());
                return Command::FAILURE;
            }
        }

        $languageCodes = $languageCode
            ? [$languageCode]
            : $this->repository->findDistinctLanguageCodes();

        if (empty($languageCodes)) {
            $io->warning('No language codes found in the word_category table. Nothing to index.');
            return Command::SUCCESS;
        }

        $io->info(sprintf('Indexing %d language(s): %s', count($languageCodes), implode(', ', $languageCodes)));

        $grandTotal = 0;

        foreach ($languageCodes as $code) {
            $io->section("Language: $code");

            $total = $this->repository->countByLanguageCode($code);
            $io->info("$total documents to index.");

            if ($total === 0) {
                $io->warning("No category data found for language '$code'. Skipping.");
                continue;
            }

            try {
                $indexed = $this->indexer->reindexByLanguage($code);
                $io->success("Indexed $indexed / $total documents for '$code'.");
                $grandTotal += $indexed;
            } catch (\Throwable $e) {
                $io->error("Failed to index language '$code': " . $e->getMessage());
            }
        }

        $io->success("Done. Total indexed: $grandTotal documents.");

        return Command::SUCCESS;
    }
}
