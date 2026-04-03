<?php

namespace App\Command;

use App\Constant\LanguageMappings;
use App\Entity\WordCategoryEntity;
use App\Repository\AbstractLanguageRepository;
use App\Repository\WordCategoryRepository;
use App\Service\Categorization\WordCategorizationService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:categorize-words',
    description: 'Fetch words for a language and assign semantic category scores via Claude API',
)]
class CategorizeWordsCommand extends Command
{
    public function __construct(
        private readonly ManagerRegistry $registry,
        private readonly WordCategoryRepository $wordCategoryRepository,
        private readonly WordCategorizationService $categorizationService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('languageCode', InputArgument::REQUIRED, 'Language code to process (e.g. en, de, nl)')
            ->addOption('batch-size', null, InputOption::VALUE_REQUIRED, 'Words per Claude API call', 20)
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Max words to process (0 = all)', 0)
            ->addOption('offset', null, InputOption::VALUE_REQUIRED, 'Start from this offset in the word list', 0)
            ->addOption('skip-existing', null, InputOption::VALUE_NONE, 'Skip words that already have category data');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $languageCode = $input->getArgument('languageCode');
        $batchSize = (int) $input->getOption('batch-size');
        $limit = (int) $input->getOption('limit');
        $offset = (int) $input->getOption('offset');
        $skipExisting = $input->getOption('skip-existing');

        $entityClass = LanguageMappings::getEntityClassByLanguageCode($languageCode);
        if ($entityClass === null) {
            $io->error("Unknown language code: $languageCode");
            return Command::FAILURE;
        }

        /** @var AbstractLanguageRepository $repository */
        $repository = $this->registry->getRepository($entityClass);

        $fetchLimit = $limit > 0 ? $limit : AbstractLanguageRepository::PRONUNCIATION_MAX_RESULTS;
        $io->info("Fetching up to $fetchLimit words for language '$languageCode' (offset $offset)…");

        $words = $repository->findAllNamesIpaAndScore($fetchLimit, $offset);

        if (empty($words)) {
            $io->warning('No words found.');
            return Command::SUCCESS;
        }

        $io->info(sprintf('Fetched %d words. Batch size: %d.', count($words), $batchSize));

        if ($skipExisting) {
            $words = array_filter($words, function (array $row) use ($languageCode): bool {
                return $this->wordCategoryRepository->findByLanguageCodeAndWord($languageCode, $row['name']) === null;
            });
            $words = array_values($words);
            $io->info(sprintf('%d words remain after skipping existing entries.', count($words)));
        }

        $batches = array_chunk($words, $batchSize);
        $total = count($words);
        $processed = 0;
        $failed = 0;

        $io->progressStart($total);

        foreach ($batches as $batch) {
            $wordNames = array_column($batch, 'name');

            try {
                $categorized = $this->categorizationService->categorize($wordNames);
            } catch (\Throwable $e) {
                $io->newLine();
                $io->warning(sprintf('Batch failed: %s — skipping %d words', $e->getMessage(), count($batch)));
                $failed += count($batch);
                $io->progressAdvance(count($batch));
                continue;
            }

            foreach ($batch as $row) {
                $word = $row['name'];
                $categories = $categorized[$word] ?? [];

                if (empty($categories)) {
                    $failed++;
                    $io->progressAdvance();
                    continue;
                }

                $entity = new WordCategoryEntity();
                $entity->setLanguageCode($languageCode);
                $entity->setWord($word);
                $entity->setCategories($categories);

                try {
                    $this->wordCategoryRepository->upsert($entity);
                    $processed++;
                } catch (\Throwable $e) {
                    $failed++;
                    $io->newLine();
                    $io->warning("Failed to save '$word': " . $e->getMessage());
                }

                $io->progressAdvance();
            }
        }

        $io->progressFinish();

        $io->success(sprintf(
            'Done. Processed: %d, Skipped/failed: %d (of %d total)',
            $processed,
            $failed,
            $total
        ));

        return Command::SUCCESS;
    }
}
