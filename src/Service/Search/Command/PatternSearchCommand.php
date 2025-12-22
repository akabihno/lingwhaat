<?php

namespace App\Service\Search\Command;

use App\Service\Search\PatternSearchService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'search:pattern',
    description: 'Search for words matching a pattern where ? represents unknown characters'
)]
class PatternSearchCommand extends Command
{
    public function __construct(
        private readonly PatternSearchService $patternSearchService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'pattern',
                InputArgument::REQUIRED,
                'Search pattern where ? represents a single unknown character (e.g., h?s?)'
            )
            ->addOption(
                'language-code',
                'l',
                InputOption::VALUE_REQUIRED,
                'Filter by language code (e.g., en, ka, ru)'
            )
            ->addOption(
                'field',
                'f',
                InputOption::VALUE_REQUIRED,
                'Field to search in: word (default) or ipa',
                'word'
            )
            ->addOption(
                'limit',
                'm',
                InputOption::VALUE_REQUIRED,
                'Maximum number of results to return',
                '100'
            )
            ->setHelp(
                <<<'HELP'
The <info>search:pattern</info> command searches for words matching a pattern.

Use <comment>?</comment> to represent a single unknown character in the pattern.

<info>Examples:</info>

  Find all 4-letter words starting with 'h' and having 's' as the 3rd letter:
    <info>php bin/console search:pattern "h?s?"</info>

  Find Georgian words matching the pattern:
    <info>php bin/console search:pattern "?ა?ა" --language-code=ka</info>

  Search in IPA field:
    <info>php bin/console search:pattern "h?ʊ?" --field=ipa</info>

  Limit results to 10:
    <info>php bin/console search:pattern "c?t" --limit=10</info>

<info>Pattern Rules:</info>
  - <comment>?</comment> matches exactly one character
  - <comment>*</comment> matches zero or more characters
  - Patterns are case-insensitive
HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pattern = $input->getArgument('pattern');
        $languageCode = $input->getOption('language-code');
        $field = $input->getOption('field');
        $limit = (int) $input->getOption('limit');

        if (!in_array($field, ['word', 'ipa'])) {
            $output->writeln('<error>Error: Field must be either "word" or "ipa"</error>');
            return Command::FAILURE;
        }

        if ($limit < 1 || $limit > 1000) {
            $output->writeln('<error>Error: Limit must be between 1 and 1000</error>');
            return Command::FAILURE;
        }

        $output->writeln("<info>Searching for pattern:</info> <comment>$pattern</comment>");
        if ($languageCode) {
            $output->writeln("<info>Language filter:</info> <comment>$languageCode</comment>");
        }
        $output->writeln("<info>Search field:</info> <comment>$field</comment>");
        $output->writeln("<info>Limit:</info> <comment>$limit</comment>");
        $output->writeln('');

        try {
            if ('ipa' == $field) {
                $results = $this->patternSearchService->findByIpaPattern($pattern, $languageCode, $limit);
            } else {
                $results = $this->patternSearchService->findByPattern($pattern, $languageCode, $limit);
            }

            if (empty($results)) {
                $output->writeln('<comment>No matches found.</comment>');
                return Command::SUCCESS;
            }

            $output->writeln("<info>Found " . count($results) . " matches:</info>");
            $output->writeln('');

            foreach ($results as $index => $result) {
                $word = $result['word'] ?? 'N/A';
                $ipa = $result['ipa'] ?? 'N/A';
                $lang = $result['languageCode'] ?? 'N/A';

                $output->writeln(sprintf(
                    '<comment>%d.</comment> <info>%s</info> [%s] (%s)',
                    $index + 1,
                    $word,
                    $ipa,
                    $lang
                ));
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}
