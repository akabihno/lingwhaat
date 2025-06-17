<?php

namespace App\Service\LanguageDetection\LanguageTransliteration;

use App\Service\LanguageDetection\LanguageDetectionService;
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

        $charMap = json_decode(file_get_contents($this->charMapPath), true);
        $vector = $this->trainIpaPredictorModelCommand->encodeWord(mb_str_split($word), $charMap);
        $dataset = new Unlabeled([$vector]);
        $ipa = '';

        for ($i = 0; $i < 10; $i++) {
            $model = PersistentModel::load(new Filesystem("{$this->modelPath}_pos_{$i}.model"));
            $ipaChar = $model->predict($dataset)[0];
            $ipa .= ($ipaChar !== '_') ? $ipaChar : '';
        }

        $output->writeln("Predicted IPA: $ipa");

        return Command::SUCCESS;
    }

}