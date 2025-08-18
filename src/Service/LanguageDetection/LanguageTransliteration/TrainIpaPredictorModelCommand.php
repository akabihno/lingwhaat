<?php

namespace App\Service\LanguageDetection\LanguageTransliteration;

use App\Service\LanguageDetection\LanguageDetectionService;
use App\Service\LanguageDetection\LanguageServices\AlbanianLanguageService;
use App\Service\LanguageDetection\LanguageServices\DutchLanguageService;
use App\Service\LanguageDetection\LanguageServices\EnglishLanguageService;
use App\Service\LanguageDetection\LanguageServices\EstonianLanguageService;
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
use App\Service\LanguageDetection\LanguageTransliteration\Constants\IpaPredictorConstants;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(name: 'ml:train:ipa-predictor')]
class TrainIpaPredictorModelCommand extends Command
{
    protected string $trainingDataPath;
    public function __construct(
        protected DutchLanguageService $dutchLanguageService,
        protected EnglishLanguageService $englishLanguageService,
        protected EstonianLanguageService $estonianLanguageService,
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
        protected AlbanianLanguageService $albanianLanguageService,
        protected HttpClientInterface $httpClient,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Train IPA prediction model for a specific language')
            ->addOption('lang', 'l', InputOption::VALUE_REQUIRED,
                'Language code in: ' . implode(', ', LanguageDetectionService::getLanguageCodes())
            )->addOption('prepare', 'p', InputOption::VALUE_OPTIONAL,
            'Optional argument to update existing dataset in CSV file (can be used if data in DB was populated)');
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // example: php bin/console ml:train:ipa-predictor --lang lv

        $lang = $input->getOption('lang');
        $prepare = $input->getOption('prepare');

        $this->trainingDataPath = realpath(".")."/ml_service/data/{$lang}.csv";

        switch ($lang) {
            case LanguageDetectionService::DUTCH_LANGUAGE_CODE:
                $trainingDatasetArray = $this->dutchLanguageService->fetchAllNamesAndIpa();
                break;
            case LanguageDetectionService::ENGLISH_LANGUAGE_CODE:
                $trainingDatasetArray = $this->englishLanguageService->fetchAllNamesAndIpa();
                break;
            case LanguageDetectionService::ESTONIAN_LANGUAGE_CODE:
                $trainingDatasetArray = $this->estonianLanguageService->fetchAllNamesAndIpa();
                break;
            case LanguageDetectionService::FRENCH_LANGUAGE_CODE:
                $trainingDatasetArray = $this->frenchLanguageService->fetchAllNamesAndIpa();
                break;
            case LanguageDetectionService::GEORGIAN_LANGUAGE_CODE:
                $trainingDatasetArray = $this->georgianLanguageService->fetchAllNamesAndIpa();
                break;
            case LanguageDetectionService::GERMAN_LANGUAGE_CODE:
                $trainingDatasetArray = $this->germanLanguageService->fetchAllNamesAndIpa();
                break;
            case LanguageDetectionService::GREEK_LANGUAGE_CODE:
                $trainingDatasetArray = $this->greekLanguageService->fetchAllNamesAndIpa();
                break;
            case LanguageDetectionService::HINDI_LANGUAGE_CODE:
                $trainingDatasetArray = $this->hindiLanguageService->fetchAllNamesAndIpa();
                break;
            case LanguageDetectionService::ITALIAN_LANGUAGE_CODE:
                $trainingDatasetArray = $this->italianLanguageService->fetchAllNamesAndIpa();
                break;
            case LanguageDetectionService::LATIN_LANGUAGE_CODE:
                $trainingDatasetArray = $this->latinLanguageService->fetchAllNamesAndIpa();
                break;
            case LanguageDetectionService::LATVIAN_LANGUAGE_CODE:
                $trainingDatasetArray = $this->latvianLanguageService->fetchAllNamesAndIpa();
                break;
            case LanguageDetectionService::LITHUANIAN_LANGUAGE_CODE:
                $trainingDatasetArray = $this->lithuanianLanguageService->fetchAllNamesAndIpa();
                break;
            case LanguageDetectionService::POLISH_LANGUAGE_CODE:
                $trainingDatasetArray = $this->polishLanguageService->fetchAllNamesAndIpa();
                break;
            case LanguageDetectionService::PORTUGUESE_LANGUAGE_CODE:
                $trainingDatasetArray = $this->portugueseLanguageService->fetchAllNamesAndIpa();
                break;
            case LanguageDetectionService::ROMANIAN_LANGUAGE_CODE:
                $trainingDatasetArray = $this->romanianLanguageService->fetchAllNamesAndIpa();
                break;
            case LanguageDetectionService::RUSSIAN_LANGUAGE_CODE:
                $trainingDatasetArray = $this->russianLanguageService->fetchAllNamesAndIpa();
                break;
            case LanguageDetectionService::SERBOCROATIAN_LANGUAGE_CODE:
                $trainingDatasetArray = $this->serboCroatianLanguageService->fetchAllNamesAndIpa();
                break;
            case LanguageDetectionService::SPANISH_LANGUAGE_CODE:
                $trainingDatasetArray = $this->spanishLanguageService->fetchAllNamesAndIpa();
                break;
            case LanguageDetectionService::SWEDISH_LANGUAGE_CODE:
                $trainingDatasetArray = $this->swedishLanguageService->fetchAllNamesAndIpa();
                break;
            case LanguageDetectionService::TAGALOG_LANGUAGE_CODE:
                $trainingDatasetArray = $this->tagalogLanguageService->fetchAllNamesAndIpa();
                break;
            case LanguageDetectionService::TURKISH_LANGUAGE_CODE:
                $trainingDatasetArray = $this->turkishLanguageService->fetchAllNamesAndIpa();
                break;
            case LanguageDetectionService::UKRAINIAN_LANGUAGE_CODE:
                $trainingDatasetArray = $this->ukrainianLanguageService->fetchAllNamesAndIpa();
                break;
            case LanguageDetectionService::ALBANIAN_LANGUAGE_CODE:
                $trainingDatasetArray = $this->albanianLanguageService->fetchAllNamesAndIpa();
                break;
            default:
                $acceptedLangCodes = implode(', ', LanguageDetectionService::getLanguageCodes());
                $output->writeln('<error>No language provided in --lang parameter or language is not accepted. 
                Accepted language params are: '.$acceptedLangCodes.' </error>');
                return Command::FAILURE;
        }

        if (!$trainingDatasetArray) {
            $output->writeln('<error>No valid data found for training.</error>');
            return Command::FAILURE;
        }


        if (!file_exists($this->trainingDataPath) || $prepare) {

            $csvHandle = fopen($this->trainingDataPath, 'w');

            fputcsv($csvHandle, ['word', 'ipa']);

            foreach ($trainingDatasetArray as $datasetRow) {
                $word = $datasetRow['name'];
                $ipa = $datasetRow['ipa'];
                if ($ipa && $word) {
                    fputcsv($csvHandle, [$word, $ipa]);
                }
            }

            fclose($csvHandle);
            $output->writeln("Training dataset for {$lang} saved to {$this->trainingDataPath}");
        }

        $file = new File($this->trainingDataPath);

        $response = $this->httpClient->request('POST', 'http://'.IpaPredictorConstants::getMlServiceHost().':'.IpaPredictorConstants::getMlServicePort().'/'.IpaPredictorConstants::getMlServiceTrainIpaRoute().'/', [
            'headers' => [
                'Content-Type' => 'multipart/form-data',
            ],
            'body' => [
                'file' => fopen($file->getRealPath(), 'r'),
            ],
            'timeout' => 600,
        ]);

        $statusCode = $response->getStatusCode();
        $content = $response->getContent();

        $output->writeln("Training complete on dataset {$this->trainingDataPath} with status {$statusCode}");


        return Command::SUCCESS;
    }

}