<?php

namespace App\Command;

use App\Message\WikipediaPatternIndexDispatchMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'app:wikipedia-pattern-index-dispatch',
    description: 'Dispatch WikipediaPatternIndexDispatchMessage immediately (index then search)',
)]
class WikipediaPatternIndexDispatchCommand extends Command
{
    public function __construct(
        private readonly MessageBusInterface $bus,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('window-size', 'w', InputOption::VALUE_REQUIRED, 'Pattern window size', 18)
            ->addOption('article-limit', 'l', InputOption::VALUE_REQUIRED, 'Articles per language per run', 100);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $windowSize = (int) $input->getOption('window-size');
        $articleLimit = (int) $input->getOption('article-limit');

        $this->bus->dispatch(new WikipediaPatternIndexDispatchMessage($windowSize, $articleLimit));

        $io->success("Dispatched WikipediaPatternIndexDispatchMessage (windowSize=$windowSize, articleLimit=$articleLimit).");

        return Command::SUCCESS;
    }
}
