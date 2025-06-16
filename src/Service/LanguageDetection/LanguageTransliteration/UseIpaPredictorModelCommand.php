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

        $this->modelPath = "src/Models/IpaPredictor/ipa_predictor_{$lang}.model";
        $this->charMapPath = "src/CharMap/{$lang}.json";

        $charMap = json_decode(file_get_contents($this->charMapPath), true);

        $vector = $this->trainIpaPredictorModelCommand->encodeCharacters(mb_str_split($word), $charMap);

        $estimator = PersistentModel::load(new Filesystem($this->modelPath));
        $dataset = new Unlabeled([$vector]);

        $ipa = json_encode($estimator->predict($dataset));

        $output->writeln("Predicted IPA: $ipa");

        return Command::SUCCESS;
    }

    protected function padVector(array $vector, int $length): array
    {
        return array_pad(array_slice($vector, 0, $length), $length, 0);
    }

}