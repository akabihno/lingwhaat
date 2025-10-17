<?php

namespace App\Service\Search\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use App\Service\Search\WordIndexer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'language:reindex-words', description: 'Rebuild Elasticsearch index from MySQL data')]
class ReindexWordsCommand extends Command
{
    public function __construct(private WordIndexer $indexer) { parent::__construct(); }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Indexing words...');
        $this->indexer->reindexAll();
        $output->writeln('Done.');
        return Command::SUCCESS;
    }
}