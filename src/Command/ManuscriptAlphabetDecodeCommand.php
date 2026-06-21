<?php

namespace App\Command;

use App\Constant\ScriptAlphabets;
use App\Message\ManuscriptAlphabetDecodeDispatchMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'app:manuscript-alphabet-decode',
    description: 'Decode manuscript pattern matches using alphabet substitution for a given language',
)]
class ManuscriptAlphabetDecodeCommand extends Command
{
    private const int DEFAULT_WINDOW_SIZE = 18;

    public function __construct(
        private readonly MessageBusInterface $bus,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->addOption(
                'language-code',
                'l',
                InputOption::VALUE_REQUIRED,
                sprintf('Target language code (e.g. en, fr, ru). Supported: %s', implode(', ', ScriptAlphabets::getSupportedLanguageCodes())),
            )
            ->addOption(
                'window-size',
                'w',
                InputOption::VALUE_OPTIONAL,
                'Sliding window size in characters',
                self::DEFAULT_WINDOW_SIZE,
            );
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $languageCode = (string) $input->getOption('language-code');
        if (empty($languageCode)) {
            $io->error('Option --language-code is required.');
            return Command::FAILURE;
        }

        if (!isset(ScriptAlphabets::LANGUAGE_TO_ALPHABET[$languageCode])) {
            $io->warning(sprintf('Language code "%s" has no registered alphabet; falling back to Latin.', $languageCode));
        }

        $windowSize = (int) $input->getOption('window-size');
        if ($windowSize < 6) {
            $io->error('Window size must be at least 6.');
            return Command::FAILURE;
        }

        $io->info(sprintf('Dispatching alphabet decode for language=%s window=%d …', $languageCode, $windowSize));

        $this->bus->dispatch(new ManuscriptAlphabetDecodeDispatchMessage($languageCode, $windowSize));

        $io->success('Dispatch message sent. Workers will process records in parallel.');

        return Command::SUCCESS;
    }
}
