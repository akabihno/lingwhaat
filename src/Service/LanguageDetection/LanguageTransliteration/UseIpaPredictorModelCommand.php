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
    protected string $charMapPath;
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

        $this->modelPath = "src/Models/IpaPredictor/ipa_predictor_{$lang}.model";
        $this->charMapPath = "src/CharMap/{$lang}.json";

        $model = (new ModelManager())->restoreFromFile($this->modelPath);
        $charMap = json_decode(file_get_contents($this->charMapPath), true);

        $vector = $this->encodeCharacters(mb_str_split($word), $charMap);
        $ipa = $model->predict([$vector])[0];

        var_dump($ipa);

        return Command::SUCCESS;
    }

    protected function encodeCharacters(array $chars, array $map, int $maxLength = 10): array
    {
        $encoded = [];

        foreach ($chars as $ch) {
            $encoded[] = $map[$ch] ?? 0;
        }

        while (count($encoded) < $maxLength) {
            $encoded[] = 0;
        }

        if (count($encoded) > $maxLength) {
            $encoded = array_slice($encoded, 0, $maxLength);
        }

        return $encoded;
    }

}