<?php

namespace App\Command;

use App\Constant\LanguageMappings;
use App\Service\Search\SubstitutionCipherSolver;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:substitution-cipher-solve',
    description: 'Treat the input as a monoalphabetic substitution cipher and print, as a JSON array of word arrays, every plaintext decoding whose words all exist in the target language and agree on one consistent letter substitution.',
)]
class SubstitutionCipherSolveCommand extends Command
{
    public function __construct(
        private readonly SubstitutionCipherSolver $solver,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->addArgument('language-code', InputArgument::REQUIRED, 'Target language code (e.g. en, fr, ru).')
            ->addArgument('text', InputArgument::REQUIRED, 'Enciphered text; words separated by whitespace.')
            ->addOption(
                'candidates-per-word',
                'c',
                InputOption::VALUE_OPTIONAL,
                'Max exact pattern matches fetched per cipher word (highest-scoring first).',
                SubstitutionCipherSolver::DEFAULT_CANDIDATES_PER_WORD,
            )
            ->addOption(
                'max-results',
                'm',
                InputOption::VALUE_OPTIONAL,
                'Max consistent decodings to return.',
                SubstitutionCipherSolver::DEFAULT_MAX_RESULTS,
            );
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $languageCode = (string) $input->getArgument('language-code');
        if (!in_array($languageCode, LanguageMappings::getLanguageCodes(), true)) {
            $io->error(sprintf('Unknown language code "%s".', $languageCode));
            return Command::INVALID;
        }

        $text = (string) $input->getArgument('text');
        $candidatesPerWord = max(1, (int) $input->getOption('candidates-per-word'));
        $maxResults = max(1, (int) $input->getOption('max-results'));

        try {
            $decodings = $this->solver->solve($languageCode, $text, $candidatesPerWord, $maxResults);
        } catch (\Throwable $e) {
            $io->error(sprintf('Search failed: %s', $e->getMessage()));
            return Command::FAILURE;
        }

        $output->writeln(json_encode($decodings, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));

        return Command::SUCCESS;
    }
}
