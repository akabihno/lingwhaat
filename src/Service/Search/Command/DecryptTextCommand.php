<?php

namespace App\Service\Search\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use App\Service\Search\TextDecryptionService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'language:decrypt-text',
    description: 'Decrypt text by trying letter substitutions and finding matches in Elasticsearch'
)]
class DecryptTextCommand extends Command
{
    public function __construct(
        private TextDecryptionService $decryptionService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'text',
                InputArgument::REQUIRED,
                'The encrypted text to decrypt'
            )
            ->addOption(
                'language-code',
                'l',
                InputOption::VALUE_REQUIRED,
                'The target language code (e.g., odt for Old Dutch)',
                'odt'
            )
            ->addOption(
                'min-count',
                'm',
                InputOption::VALUE_REQUIRED,
                'Minimum number of words that must match',
                5
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $text = $input->getArgument('text');
        $languageCode = $input->getOption('language-code');
        $minCount = (int) $input->getOption('min-count');

        $output->writeln("<info>Attempting to decrypt text for language: {$languageCode}</info>");
        $output->writeln("<info>Minimum required matches: {$minCount}</info>");
        $output->writeln('');

        $output->writeln('<comment>Original text:</comment>');
        $output->writeln($text);
        $output->writeln('');

        $output->writeln('<info>Analyzing text and trying substitutions...</info>');
        $result = $this->decryptionService->decryptText($text, $languageCode, $minCount);


        $output->writeln('');
        $output->writeln(str_repeat('=', 80));

        if ($result['success']) {
            $output->writeln('<info>✓ Successfully decrypted text!</info>');
        } else {
            $output->writeln('<error>✗ Could not fully decrypt text (found ' . $result['match_count'] . ' matches, needed ' . $minCount . ')</error>');
        }

        $output->writeln(str_repeat('=', 80));
        $output->writeln('');

        $output->writeln('<comment>Decrypted text:</comment>');
        $output->writeln('<info>' . $result['decrypted_text'] . '</info>');
        $output->writeln('');

        $output->writeln('<comment>Statistics:</comment>');
        $output->writeln("  Match count: <info>{$result['match_count']}</info>");
        $output->writeln("  Required minimum: <info>{$result['min_count']}</info>");
        $output->writeln('');

        if (!empty($result['substitutions'])) {
            $output->writeln('<comment>Letter substitutions applied:</comment>');
            foreach ($result['substitutions'] as $from => $to) {
                $output->writeln("  {$from} → {$to}");
            }
            $output->writeln('');
        }

        if (!empty($result['matched_words'])) {
            $output->writeln('<comment>Matched words found in Elasticsearch:</comment>');
            $matchedWordsStr = implode(', ', array_slice($result['matched_words'], 0, 20));
            if (count($result['matched_words']) > 20) {
                $matchedWordsStr .= ' ... (and ' . (count($result['matched_words']) - 20) . ' more)';
            }
            $output->writeln("  {$matchedWordsStr}");
            $output->writeln('');
        }

        return $result['success'] ? Command::SUCCESS : Command::FAILURE;
    }
}
