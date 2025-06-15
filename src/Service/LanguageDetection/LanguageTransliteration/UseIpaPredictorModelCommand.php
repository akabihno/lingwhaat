<?php

namespace App\Service\LanguageDetection\LanguageTransliteration;

use App\Service\LanguageDetection\LanguageDetectionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Doctrine\ORM\EntityManagerInterface;
use Phpml\Classification\KNearestNeighbors;
use Phpml\ModelManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'ml:use:ipa-predictor')]
class UseIpaPredictorModelCommand extends Command
{
    protected string $modelPath;
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Use IPA prediction model for a specific language.')
            ->addOption('lang', null, InputOption::VALUE_REQUIRED,
                'Language code in: ' . implode(', ', LanguageDetectionService::getLanguageCodes())
            )
            ->addOption('word', null, InputOption::VALUE_REQUIRED, 'Word to use for IPA prediction.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $lang = $input->getOption('lang');
        $word = $input->getOption('word');

        $this->modelPath = "src/Models/ipa_predictor_{$lang}.model";

        $model = (new ModelManager())->restoreFromFile($this->modelPath);

        $input = $this->encodeCharacters(mb_str_split($word), $charMap);
        $prediction = $model->predict([$input]);

        echo $prediction[0];

        return Command::SUCCESS;
    }

    protected function encodeCharacters(array $chars, array $map): array
    {
        $encoded = [];
        foreach ($chars as $ch) {
            $encoded[] = $map[$ch] ?? 0;
        }

        return $encoded;
    }

}