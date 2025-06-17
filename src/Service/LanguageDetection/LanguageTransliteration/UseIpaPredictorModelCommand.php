<?php

namespace App\Service\LanguageDetection\LanguageTransliteration;

use App\Service\LanguageDetection\LanguageDetectionService;
use Rubix\ML\Datasets\Labeled;
use Rubix\ML\Datasets\Unlabeled;
use Rubix\ML\PersistentModel;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Rubix\ML\Persisters\Filesystem;

#[AsCommand(name: 'ml:use:ipa-predictor')]
class UseIpaPredictorModelCommand extends Command
{
    protected string $reverseIpaCharMapPath;
    protected string $modelPath;
    protected string $charMapPath;
    public function __construct(protected TrainIpaPredictorModelCommand $trainIpaPredictorModelCommand)
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

        if (!$lang || !$word) {
            $output->writeln('<error>No --lang and/or --word parameters provided.</error>');
            return Command::FAILURE;
        }

        $this->modelPath = "src/Models/IpaPredictor/ipa_predictor_{$lang}";
        $this->charMapPath = "src/CharMap/{$lang}.json";

        $this->reverseIpaCharMapPath = "src/CharMap/reverse_ipa_{$lang}.json";
        $ipaCharMapReverse = json_decode(file_get_contents($this->reverseIpaCharMapPath), true);

        $charMap = json_decode(file_get_contents($this->charMapPath), true);
        $vector = $this->trainIpaPredictorModelCommand->encodeWord(mb_str_split($word), $charMap);
        $dataset = new Unlabeled($vector);
        $ipa = '';

        dump('dataset:');
        dump(json_encode($dataset));

        for ($i = 0; $i < $this->trainIpaPredictorModelCommand::IPA_LENGTH; $i++) {
            $model = PersistentModel::load(new Filesystem("{$this->modelPath}_pos_{$i}.model"));
            $index = $model->predict($dataset)[0];
            dump('index:');
            dump($index);
            $ipaChar = $ipaCharMapReverse[$index] ?? '';
            dump('ipaChar:');
            dump($ipaChar);
            $ipa .= ($ipaChar !== '_') ? $ipaChar : '';
        }

        $output->writeln("Predicted IPA: $ipa");

        return Command::SUCCESS;
    }

}