<?php

namespace App\Service\LanguageDetection\LanguageTransliteration\Command;

use App\Constant\LanguageServicesAndCodes;
use App\Repository\AfarLanguageRepository;
use App\Repository\AfrikaansLanguageRepository;
use App\Repository\AlbanianLanguageRepository;
use App\Repository\ArabicLanguageRepository;
use App\Repository\ArmenianLanguageRepository;
use App\Repository\BengaliLanguageRepository;
use App\Repository\BretonLanguageRepository;
use App\Repository\CzechLanguageRepository;
use App\Repository\DutchLanguageRepository;
use App\Repository\EnglishLanguageRepository;
use App\Repository\EstonianLanguageRepository;
use App\Repository\FrenchLanguageRepository;
use App\Repository\GeorgianLanguageRepository;
use App\Repository\GermanLanguageRepository;
use App\Repository\GreekLanguageRepository;
use App\Repository\HindiLanguageRepository;
use App\Repository\ItalianLanguageRepository;
use App\Repository\KazakhLanguageRepository;
use App\Repository\LatinLanguageRepository;
use App\Repository\LatvianLanguageRepository;
use App\Repository\LithuanianLanguageRepository;
use App\Repository\MiddleDutchLanguageRepository;
use App\Repository\OldDutchLanguageRepository;
use App\Repository\PolishLanguageRepository;
use App\Repository\PortugueseLanguageRepository;
use App\Repository\RomanianLanguageRepository;
use App\Repository\RussianLanguageRepository;
use App\Repository\SerboCroatianLanguageRepository;
use App\Repository\SpanishLanguageRepository;
use App\Repository\SwedishLanguageRepository;
use App\Repository\TagalogLanguageRepository;
use App\Repository\TurkishLanguageRepository;
use App\Repository\UkrainianLanguageRepository;
use App\Repository\UzbekLanguageRepository;
use App\Service\LanguageDetection\LanguageTransliteration\Constants\IpaPredictorConstants;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
        protected DutchLanguageRepository $dutchLanguageRepository,
        protected EnglishLanguageRepository $englishLanguageRepository,
        protected EstonianLanguageRepository $estonianLanguageRepository,
        protected FrenchLanguageRepository $frenchLanguageRepository,
        protected GeorgianLanguageRepository $georgianLanguageRepository,
        protected GermanLanguageRepository $germanLanguageRepository,
        protected GreekLanguageRepository $greekLanguageRepository,
        protected HindiLanguageRepository $hindiLanguageRepository,
        protected ItalianLanguageRepository $italianLanguageRepository,
        protected LatinLanguageRepository $latinLanguageRepository,
        protected LatvianLanguageRepository $latvianLanguageRepository,
        protected LithuanianLanguageRepository $lithuanianLanguageRepository,
        protected PolishLanguageRepository $polishLanguageRepository,
        protected PortugueseLanguageRepository $portugueseLanguageRepository,
        protected RomanianLanguageRepository $romanianLanguageRepository,
        protected RussianLanguageRepository $russianLanguageRepository,
        protected SerboCroatianLanguageRepository $serboCroatianLanguageRepository,
        protected SpanishLanguageRepository $spanishLanguageRepository,
        protected SwedishLanguageRepository $swedishLanguageRepository,
        protected TagalogLanguageRepository $tagalogLanguageRepository,
        protected TurkishLanguageRepository $turkishLanguageRepository,
        protected UkrainianLanguageRepository $ukrainianLanguageRepository,
        protected AlbanianLanguageRepository $albanianLanguageRepository,
        protected CzechLanguageRepository $czechLanguageRepository,
        protected AfrikaansLanguageRepository $afrikaansLanguageRepository,
        protected ArmenianLanguageRepository $armenianLanguageRepository,
        protected AfarLanguageRepository $afarLanguageRepository,
        protected BengaliLanguageRepository $bengaliLanguageRepository,
        protected UzbekLanguageRepository $uzbekLanguageRepository,
        protected BretonLanguageRepository $bretonLanguageRepository,
        protected KazakhLanguageRepository $kazakhLanguageRepository,
        protected ArabicLanguageRepository $arabicLanguageRepository,
        protected OldDutchLanguageRepository $oldDutchLanguageRepository,
        protected MiddleDutchLanguageRepository $middleDutchLanguageRepository,
        protected HttpClientInterface $httpClient,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Train IPA prediction model for a specific language')
            ->addOption('lang', 'l', InputOption::VALUE_REQUIRED,
                'Language code in: ' . implode(', ', LanguageServicesAndCodes::getLanguageCodes())
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
            case LanguageServicesAndCodes::DUTCH_LANGUAGE_CODE:
                $trainingDatasetArray = $this->dutchLanguageRepository->findAllNamesAndIpa();
                break;
            case LanguageServicesAndCodes::ENGLISH_LANGUAGE_CODE:
                $trainingDatasetArray = $this->englishLanguageRepository->findAllNamesAndIpa();
                break;
            case LanguageServicesAndCodes::ESTONIAN_LANGUAGE_CODE:
                $trainingDatasetArray = $this->estonianLanguageRepository->findAllNamesAndIpa();
                break;
            case LanguageServicesAndCodes::FRENCH_LANGUAGE_CODE:
                $trainingDatasetArray = $this->frenchLanguageRepository->findAllNamesAndIpa();
                break;
            case LanguageServicesAndCodes::GEORGIAN_LANGUAGE_CODE:
                $trainingDatasetArray = $this->georgianLanguageRepository->findAllNamesAndIpa();
                break;
            case LanguageServicesAndCodes::GERMAN_LANGUAGE_CODE:
                $trainingDatasetArray = $this->germanLanguageRepository->findAllNamesAndIpa();
                break;
            case LanguageServicesAndCodes::GREEK_LANGUAGE_CODE:
                $trainingDatasetArray = $this->greekLanguageRepository->findAllNamesAndIpa();
                break;
            case LanguageServicesAndCodes::HINDI_LANGUAGE_CODE:
                $trainingDatasetArray = $this->hindiLanguageRepository->findAllNamesAndIpa();
                break;
            case LanguageServicesAndCodes::ITALIAN_LANGUAGE_CODE:
                $trainingDatasetArray = $this->italianLanguageRepository->findAllNamesAndIpa();
                break;
            case LanguageServicesAndCodes::LATIN_LANGUAGE_CODE:
                $trainingDatasetArray = $this->latinLanguageRepository->findAllNamesAndIpa();
                break;
            case LanguageServicesAndCodes::LATVIAN_LANGUAGE_CODE:
                $trainingDatasetArray = $this->latvianLanguageRepository->findAllNamesAndIpa();
                break;
            case LanguageServicesAndCodes::LITHUANIAN_LANGUAGE_CODE:
                $trainingDatasetArray = $this->lithuanianLanguageRepository->findAllNamesAndIpa();
                break;
            case LanguageServicesAndCodes::POLISH_LANGUAGE_CODE:
                $trainingDatasetArray = $this->polishLanguageRepository->findAllNamesAndIpa();
                break;
            case LanguageServicesAndCodes::PORTUGUESE_LANGUAGE_CODE:
                $trainingDatasetArray = $this->portugueseLanguageRepository->findAllNamesAndIpa();
                break;
            case LanguageServicesAndCodes::ROMANIAN_LANGUAGE_CODE:
                $trainingDatasetArray = $this->romanianLanguageRepository->findAllNamesAndIpa();
                break;
            case LanguageServicesAndCodes::RUSSIAN_LANGUAGE_CODE:
                $trainingDatasetArray = $this->russianLanguageRepository->findAllNamesAndIpa();
                break;
            case LanguageServicesAndCodes::SERBOCROATIAN_LANGUAGE_CODE:
                $trainingDatasetArray = $this->serboCroatianLanguageRepository->findAllNamesAndIpa();
                break;
            case LanguageServicesAndCodes::SPANISH_LANGUAGE_CODE:
                $trainingDatasetArray = $this->spanishLanguageRepository->findAllNamesAndIpa();
                break;
            case LanguageServicesAndCodes::SWEDISH_LANGUAGE_CODE:
                $trainingDatasetArray = $this->swedishLanguageRepository->findAllNamesAndIpa();
                break;
            case LanguageServicesAndCodes::TAGALOG_LANGUAGE_CODE:
                $trainingDatasetArray = $this->tagalogLanguageRepository->findAllNamesAndIpa();
                break;
            case LanguageServicesAndCodes::TURKISH_LANGUAGE_CODE:
                $trainingDatasetArray = $this->turkishLanguageRepository->findAllNamesAndIpa();
                break;
            case LanguageServicesAndCodes::UKRAINIAN_LANGUAGE_CODE:
                $trainingDatasetArray = $this->ukrainianLanguageRepository->findAllNamesAndIpa();
                break;
            case LanguageServicesAndCodes::ALBANIAN_LANGUAGE_CODE:
                $trainingDatasetArray = $this->albanianLanguageRepository->findAllNamesAndIpa();
                break;
            case LanguageServicesAndCodes::CZECH_LANGUAGE_CODE:
                $trainingDatasetArray = $this->czechLanguageRepository->findAllNamesAndIpa();
                break;
            case LanguageServicesAndCodes::AFRIKAANS_LANGUAGE_CODE:
                $trainingDatasetArray = $this->afrikaansLanguageRepository->findAllNamesAndIpa();
                break;
            case LanguageServicesAndCodes::ARMENIAN_LANGUAGE_CODE:
                $trainingDatasetArray = $this->armenianLanguageRepository->findAllNamesAndIpa();
                break;
            case LanguageServicesAndCodes::AFAR_LANGUAGE_CODE:
                $trainingDatasetArray = $this->afarLanguageRepository->findAllNamesAndIpa();
                break;
            case LanguageServicesAndCodes::BENGALI_LANGUAGE_CODE:
                $trainingDatasetArray = $this->bengaliLanguageRepository->findAllNamesAndIpa();
                break;
            case LanguageServicesAndCodes::UZBEK_LANGUAGE_CODE:
                $trainingDatasetArray = $this->uzbekLanguageRepository->findAllNamesAndIpa();
                break;
            case LanguageServicesAndCodes::BRETON_LANGUAGE_CODE:
                $trainingDatasetArray = $this->bretonLanguageRepository->findAllNamesAndIpa();
                break;
            case LanguageServicesAndCodes::KAZAKH_LANGUAGE_CODE:
                $trainingDatasetArray = $this->kazakhLanguageRepository->findAllNamesAndIpa();
                break;
            case LanguageServicesAndCodes::ARABIC_LANGUAGE_CODE:
                $trainingDatasetArray = $this->arabicLanguageRepository->findAllNamesAndIpa();
                break;
            case LanguageServicesAndCodes::OLD_DUTCH_LANGUAGE_CODE:
                $trainingDatasetArray = $this->oldDutchLanguageRepository->findAllNamesAndIpa();
                break;
            case LanguageServicesAndCodes::MIDDLE_DUTCH_LANGUAGE_CODE:
                $trainingDatasetArray = $this->middleDutchLanguageRepository->findAllNamesAndIpa();
                break;
            default:
                $acceptedLangCodes = implode(', ', LanguageServicesAndCodes::getLanguageCodes());
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