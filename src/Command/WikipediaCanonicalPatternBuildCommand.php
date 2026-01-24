<?php

namespace App\Command;

use App\Service\CanonicalPatternBuilderService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:wikipedia-canonical-pattern-build',
    description: 'Build canonical patterns for Wikipedia articles',
)]
class WikipediaCanonicalPatternBuildCommand extends Command
{
    public function __construct(
        private readonly CanonicalPatternBuilderService $patternBuilderService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'limit',
            'l',
            InputOption::VALUE_OPTIONAL,
            'Process up to this many unprocessed articles',
            100
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $limit = (int) $input->getOption('limit');

        $io->info("Building patterns for up to $limit pending Wikipedia articles...");
        $count = $this->patternBuilderService->processPendingArticles($limit);

        $io->success("Built canonical patterns for $count articles!");

        return Command::SUCCESS;
    }
}