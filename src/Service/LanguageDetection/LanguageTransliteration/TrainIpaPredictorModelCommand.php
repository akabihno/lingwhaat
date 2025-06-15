<?php

namespace App\Service\LanguageDetection\LanguageTransliteration;

use App\Service\LanguageDetection\LanguageDetectionService;
use App\Service\LanguageDetection\LanguageServices\DutchLanguageService;
use App\Service\LanguageDetection\LanguageServices\EnglishLanguageService;
use App\Service\LanguageDetection\LanguageServices\EstonianLanguageService;
use App\Service\LanguageDetection\LanguageServices\EsuLanguageService;
use App\Service\LanguageDetection\LanguageServices\FrenchLanguageService;
use App\Service\LanguageDetection\LanguageServices\GeorgianLanguageService;
use App\Service\LanguageDetection\LanguageServices\GermanLanguageService;
use App\Service\LanguageDetection\LanguageServices\GreekLanguageService;
use App\Service\LanguageDetection\LanguageServices\HindiLanguageService;
use App\Service\LanguageDetection\LanguageServices\ItalianLanguageService;
use App\Service\LanguageDetection\LanguageServices\LatinLanguageService;
use App\Service\LanguageDetection\LanguageServices\LatvianLanguageService;
use App\Service\LanguageDetection\LanguageServices\LithuanianLanguageService;
use App\Service\LanguageDetection\LanguageServices\PolishLanguageService;
use App\Service\LanguageDetection\LanguageServices\PortugueseLanguageService;
use App\Service\LanguageDetection\LanguageServices\RomanianLanguageService;
use App\Service\LanguageDetection\LanguageServices\RussianLanguageService;
use App\Service\LanguageDetection\LanguageServices\SerboCroatianLanguageService;
use App\Service\LanguageDetection\LanguageServices\SpanishLanguageService;
use App\Service\LanguageDetection\LanguageServices\SwedishLanguageService;
use App\Service\LanguageDetection\LanguageServices\TagalogLanguageService;
use App\Service\LanguageDetection\LanguageServices\TurkishLanguageService;
use App\Service\LanguageDetection\LanguageServices\UkrainianLanguageService;
use Doctrine\ORM\EntityManagerInterface;
use Phpml\Classification\KNearestNeighbors;
use Phpml\ModelManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'ml:train:ipa-predictor')]
class TrainIpaPredictorModelCommand extends Command
{
    protected string $modelPath;
    protected string $charMapPath;

    public function __construct(
        protected DutchLanguageService $dutchLanguageService,
        protected EnglishLanguageService $englishLanguageService,
        protected EstonianLanguageService $estonianLanguageService,
        protected EsuLanguageService $esuLanguageService,
        protected FrenchLanguageService $frenchLanguageService,
        protected GeorgianLanguageService $georgianLanguageService,
        protected GermanLanguageService $germanLanguageService,
        protected GreekLanguageService $greekLanguageService,
        protected HindiLanguageService $hindiLanguageService,
        protected ItalianLanguageService $italianLanguageService,
        protected LatinLanguageService $latinLanguageService,
        protected LatvianLanguageService $latvianLanguageService,
        protected LithuanianLanguageService $lithuanianLanguageService,
        protected PolishLanguageService $polishLanguageService,
        protected PortugueseLanguageService $portugueseLanguageService,
        protected RomanianLanguageService $romanianLanguageService,
        protected RussianLanguageService $russianLanguageService,
        protected SerboCroatianLanguageService $serboCroatianLanguageService,
        protected SpanishLanguageService $spanishLanguageService,
        protected SwedishLanguageService $swedishLanguageService,
        protected TagalogLanguageService $tagalogLanguageService,
        protected TurkishLanguageService $turkishLanguageService,
        protected UkrainianLanguageService $ukrainianLanguageService,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Train IPA prediction model for a specific language.')
            ->addOption('lang', null, InputOption::VALUE_REQUIRED,
                'Language code in: ' . implode(', ', LanguageDetectionService::getLanguageCodes())
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // example: php bin/console ml:train:ipa-predictor --lang en

        $lang = $input->getOption('lang');

        $wordsArray = [];
        $this->modelPath = "src/Models/IpaPredictor/ipa_predictor_{$lang}.model";
        $this->charMapPath = "src/CharMap/{$lang}.json";

        switch ($lang) {
            case LanguageDetectionService::DUTCH_LANGUAGE_CODE:
                $wordsArray = $this->dutchLanguageService->fetchAllNamesAndIpa();
                break;
            case LanguageDetectionService::ENGLISH_LANGUAGE_CODE:
                $wordsArray = $this->englishLanguageService->fetchAllNamesAndIpa();
                break;
            case LanguageDetectionService::ESTONIAN_LANGUAGE_CODE:
                $wordsArray = $this->estonianLanguageService->fetchAllNamesAndIpa();
                break;
            case LanguageDetectionService::ESU_LANGUAGE_CODE:
                $wordsArray = $this->esuLanguageService->fetchAllNamesAndIpa();
                break;
            case LanguageDetectionService::FRENCH_LANGUAGE_CODE:
                $wordsArray = $this->frenchLanguageService->fetchAllNamesAndIpa();
                break;
            case LanguageDetectionService::GEORGIAN_LANGUAGE_CODE:
                $wordsArray = $this->georgianLanguageService->fetchAllNamesAndIpa();
                break;
            case LanguageDetectionService::GERMAN_LANGUAGE_CODE:
                $wordsArray = $this->germanLanguageService->fetchAllNamesAndIpa();
                break;
            case LanguageDetectionService::GREEK_LANGUAGE_CODE:
                $wordsArray = $this->greekLanguageService->fetchAllNamesAndIpa();
                break;
            case LanguageDetectionService::HINDI_LANGUAGE_CODE:
                $wordsArray = $this->hindiLanguageService->fetchAllNamesAndIpa();
                break;
            case LanguageDetectionService::ITALIAN_LANGUAGE_CODE:
                $wordsArray = $this->italianLanguageService->fetchAllNamesAndIpa();
                break;
            case LanguageDetectionService::LATIN_LANGUAGE_CODE:
                $wordsArray = $this->latinLanguageService->fetchAllNamesAndIpa();
                break;
            case LanguageDetectionService::LATVIAN_LANGUAGE_CODE:
                $wordsArray = $this->latvianLanguageService->fetchAllNamesAndIpa();
                break;
            case LanguageDetectionService::LITHUANIAN_LANGUAGE_CODE:
                $wordsArray = $this->lithuanianLanguageService->fetchAllNamesAndIpa();
                break;
            case LanguageDetectionService::POLISH_LANGUAGE_CODE:
                $wordsArray = $this->polishLanguageService->fetchAllNamesAndIpa();
                break;
            case LanguageDetectionService::PORTUGUESE_LANGUAGE_CODE:
                $wordsArray = $this->portugueseLanguageService->fetchAllNamesAndIpa();
                break;
            case LanguageDetectionService::ROMANIAN_LANGUAGE_CODE:
                $wordsArray = $this->romanianLanguageService->fetchAllNamesAndIpa();
                break;
            case LanguageDetectionService::RUSSIAN_LANGUAGE_CODE:
                $wordsArray = $this->russianLanguageService->fetchAllNamesAndIpa();
                break;
            case LanguageDetectionService::SERBOCROATIAN_LANGUAGE_CODE:
                $wordsArray = $this->serboCroatianLanguageService->fetchAllNamesAndIpa();
                break;
            case LanguageDetectionService::SPANISH_LANGUAGE_CODE:
                $wordsArray = $this->spanishLanguageService->fetchAllNamesAndIpa();
                break;
            case LanguageDetectionService::SWEDISH_LANGUAGE_CODE:
                $wordsArray = $this->swedishLanguageService->fetchAllNamesAndIpa();
                break;
            case LanguageDetectionService::TAGALOG_LANGUAGE_CODE:
                $wordsArray = $this->tagalogLanguageService->fetchAllNamesAndIpa();
                break;
            case LanguageDetectionService::TURKISH_LANGUAGE_CODE:
                $wordsArray = $this->turkishLanguageService->fetchAllNamesAndIpa();
                break;
            case LanguageDetectionService::UKRAINIAN_LANGUAGE_CODE:
                $wordsArray = $this->ukrainianLanguageService->fetchAllNamesAndIpa();
                break;
            default:
                $acceptedLangCodes = implode(', ', LanguageDetectionService::getLanguageCodes());
                $output->writeln('<error>No language provided in --lang parameter or language is not accepted. 
                Accepted language params are: '.$acceptedLangCodes.' </error>');
                return Command::FAILURE;
        }

        if (!$wordsArray) {
            $output->writeln('<error>No valid data found for training.</error>');
            return Command::FAILURE;
        }

        $charMap = $this->buildCharMap($wordsArray);
        $samples = [];
        $labels = [];

        foreach ($wordsArray as $key => $wordArray) {
            $charVec = $this->encodeCharacters(mb_str_split($wordArray['name']), $charMap);
            $samples[] = $charVec;
            $labels[] = $this->cleanIpa($wordArray['ipa']);
        }

        $classifier = new KNearestNeighbors();
        $classifier->train($samples, $labels);

        $manager = new ModelManager();
        $manager->saveToFile($classifier, $this->modelPath);
        file_put_contents($this->charMapPath, json_encode($charMap));

        $output->writeln("<info>Model trained and saved to: {$this->modelPath}</info>");
        return Command::SUCCESS;
    }

    protected function cleanIpa(string $ipa): string
    {
        return str_replace(['[', ']', '/'], '', $ipa);
    }

    protected function buildCharMap(array $wordsArray): array
    {
        $map = [];
        $index = 1;

        foreach ($wordsArray as $key => $wordArray) {
            foreach (mb_str_split($wordArray['name']) as $char) {
                if (!isset($map[$char])) {
                    $map[$char] = $index++;
                }
            }
        }

        return $map;
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