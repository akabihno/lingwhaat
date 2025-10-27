<?php

namespace App\Service\LanguageDetection\Command;

use App\Constant\LanguageServicesAndCodes;
use App\Entity\UniquePatternEntity;
use App\Repository\UniquePatternRepository;
use App\Service\LanguageDetection\LanguageDetectionService;
use App\Service\LanguageDetection\LanguageServices\AfarLanguageService;
use App\Service\LanguageDetection\LanguageServices\AfrikaansLanguageService;
use App\Service\LanguageDetection\LanguageServices\AlbanianLanguageService;
use App\Service\LanguageDetection\LanguageServices\ArmenianLanguageService;
use App\Service\LanguageDetection\LanguageServices\BengaliLanguageService;
use App\Service\LanguageDetection\LanguageServices\CzechLanguageService;
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
use App\Service\LanguageDetection\LanguageServices\UzbekLanguageService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'language:sequence:set')]
class SetUniqueLetterSequenceCommand extends Command
{
    private const int SEQUENCE_COUNT = 4;
    private const int WORDS_LIMIT = 100;
    private const string SEQUENCE_POSITION = 'start';
    private array $languageServiceMap = [];
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
        protected CzechLanguageService $czechLanguageService,
        protected AfrikaansLanguageService $afrikaansLanguageService,
        protected ArmenianLanguageService $armenianLanguageService,
        protected AfarLanguageService $afarLanguageService,
        protected BengaliLanguageService $bengaliLanguageService,
        protected UzbekLanguageService $uzbekLanguageService,
        protected UniquePatternRepository $uniquePatternRepository,
    ) {
        parent::__construct();

        $this->languageServiceMap = [
            LanguageServicesAndCodes::DUTCH_LANGUAGE_CODE => $this->dutchLanguageService,
            LanguageServicesAndCodes::ENGLISH_LANGUAGE_CODE => $this->englishLanguageService,
            LanguageServicesAndCodes::ESTONIAN_LANGUAGE_CODE => $this->estonianLanguageService,
            LanguageServicesAndCodes::FRENCH_LANGUAGE_CODE => $this->frenchLanguageService,
            LanguageServicesAndCodes::GEORGIAN_LANGUAGE_CODE => $this->georgianLanguageService,
            LanguageServicesAndCodes::GERMAN_LANGUAGE_CODE => $this->germanLanguageService,
            LanguageServicesAndCodes::GREEK_LANGUAGE_CODE => $this->greekLanguageService,
            LanguageServicesAndCodes::HINDI_LANGUAGE_CODE => $this->hindiLanguageService,
            LanguageServicesAndCodes::ITALIAN_LANGUAGE_CODE => $this->italianLanguageService,
            LanguageServicesAndCodes::LATIN_LANGUAGE_CODE => $this->latinLanguageService,
            LanguageServicesAndCodes::LATVIAN_LANGUAGE_CODE => $this->latvianLanguageService,
            LanguageServicesAndCodes::LITHUANIAN_LANGUAGE_CODE => $this->lithuanianLanguageService,
            LanguageServicesAndCodes::POLISH_LANGUAGE_CODE => $this->polishLanguageService,
            LanguageServicesAndCodes::PORTUGUESE_LANGUAGE_CODE => $this->portugueseLanguageService,
            LanguageServicesAndCodes::ROMANIAN_LANGUAGE_CODE => $this->romanianLanguageService,
            LanguageServicesAndCodes::RUSSIAN_LANGUAGE_CODE => $this->russianLanguageService,
            LanguageServicesAndCodes::SERBOCROATIAN_LANGUAGE_CODE => $this->serboCroatianLanguageService,
            LanguageServicesAndCodes::SPANISH_LANGUAGE_CODE => $this->spanishLanguageService,
            LanguageServicesAndCodes::SWEDISH_LANGUAGE_CODE => $this->swedishLanguageService,
            LanguageServicesAndCodes::TAGALOG_LANGUAGE_CODE => $this->tagalogLanguageService,
            LanguageServicesAndCodes::TURKISH_LANGUAGE_CODE => $this->turkishLanguageService,
            LanguageServicesAndCodes::UKRAINIAN_LANGUAGE_CODE => $this->ukrainianLanguageService,
            LanguageServicesAndCodes::ALBANIAN_LANGUAGE_CODE => $this->albanianLanguageService,
            LanguageServicesAndCodes::CZECH_LANGUAGE_CODE => $this->czechLanguageService,
            LanguageServicesAndCodes::AFRIKAANS_LANGUAGE_CODE => $this->afrikaansLanguageService,
            LanguageServicesAndCodes::ARMENIAN_LANGUAGE_CODE => $this->armenianLanguageService,
            LanguageServicesAndCodes::AFAR_LANGUAGE_CODE => $this->afarLanguageService,
            LanguageServicesAndCodes::BENGALI_LANGUAGE_CODE => $this->bengaliLanguageService,
            LanguageServicesAndCodes::UZBEK_LANGUAGE_CODE => $this->uzbekLanguageService,
        ];
    }
    protected function configure(): void
    {
        $this
            ->setDescription('Calculate and store unique letter sequence for a specific language')
            ->addOption('lang', 'l', InputOption::VALUE_REQUIRED,
                'Language code in: ' . implode(', ', LanguageServicesAndCodes::getLanguageCodes())
            )->addOption('limit', 'lim', InputOption::VALUE_OPTIONAL,
            'Limit the number of words to be processed', self::WORDS_LIMIT);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // example: php bin/console language:sequence:set --lang lv

        $lang = $input->getOption('lang');
        $limit = $input->getOption('limit');

        switch ($lang) {
            case LanguageServicesAndCodes::DUTCH_LANGUAGE_CODE:
                $datasetArray = $this->dutchLanguageService->fetchAllNamesWithoutUniquePatternCheck($limit);
                $service = $this->dutchLanguageService;
                break;
            case LanguageServicesAndCodes::ENGLISH_LANGUAGE_CODE:
                $datasetArray = $this->englishLanguageService->fetchAllNamesWithoutUniquePatternCheck($limit);
                $service = $this->englishLanguageService;
                break;
            case LanguageServicesAndCodes::ESTONIAN_LANGUAGE_CODE:
                $datasetArray = $this->estonianLanguageService->fetchAllNamesWithoutUniquePatternCheck($limit);
                $service = $this->estonianLanguageService;
                break;
            case LanguageServicesAndCodes::FRENCH_LANGUAGE_CODE:
                $datasetArray = $this->frenchLanguageService->fetchAllNamesWithoutUniquePatternCheck($limit);
                $service = $this->frenchLanguageService;
                break;
            case LanguageServicesAndCodes::GEORGIAN_LANGUAGE_CODE:
                $datasetArray = $this->georgianLanguageService->fetchAllNamesWithoutUniquePatternCheck($limit);
                $service = $this->georgianLanguageService;
                break;
            case LanguageServicesAndCodes::GERMAN_LANGUAGE_CODE:
                $datasetArray = $this->germanLanguageService->fetchAllNamesWithoutUniquePatternCheck($limit);
                $service = $this->germanLanguageService;
                break;
            case LanguageServicesAndCodes::GREEK_LANGUAGE_CODE:
                $datasetArray = $this->greekLanguageService->fetchAllNamesWithoutUniquePatternCheck($limit);
                $service = $this->greekLanguageService;
                break;
            case LanguageServicesAndCodes::HINDI_LANGUAGE_CODE:
                $datasetArray = $this->hindiLanguageService->fetchAllNamesWithoutUniquePatternCheck($limit);
                $service = $this->hindiLanguageService;
                break;
            case LanguageServicesAndCodes::ITALIAN_LANGUAGE_CODE:
                $datasetArray = $this->italianLanguageService->fetchAllNamesWithoutUniquePatternCheck($limit);
                $service = $this->italianLanguageService;
                break;
            case LanguageServicesAndCodes::LATIN_LANGUAGE_CODE:
                $datasetArray = $this->latinLanguageService->fetchAllNamesWithoutUniquePatternCheck($limit);
                $service = $this->latinLanguageService;
                break;
            case LanguageServicesAndCodes::LATVIAN_LANGUAGE_CODE:
                $datasetArray = $this->latvianLanguageService->fetchAllNamesWithoutUniquePatternCheck($limit);
                $service = $this->latvianLanguageService;
                break;
            case LanguageServicesAndCodes::LITHUANIAN_LANGUAGE_CODE:
                $datasetArray = $this->lithuanianLanguageService->fetchAllNamesWithoutUniquePatternCheck($limit);
                $service = $this->lithuanianLanguageService;
                break;
            case LanguageServicesAndCodes::POLISH_LANGUAGE_CODE:
                $datasetArray = $this->polishLanguageService->fetchAllNamesWithoutUniquePatternCheck($limit);
                $service = $this->polishLanguageService;
                break;
            case LanguageServicesAndCodes::PORTUGUESE_LANGUAGE_CODE:
                $datasetArray = $this->portugueseLanguageService->fetchAllNamesWithoutUniquePatternCheck($limit);
                $service = $this->portugueseLanguageService;
                break;
            case LanguageServicesAndCodes::ROMANIAN_LANGUAGE_CODE:
                $datasetArray = $this->romanianLanguageService->fetchAllNamesWithoutUniquePatternCheck($limit);
                $service = $this->romanianLanguageService;
                break;
            case LanguageServicesAndCodes::RUSSIAN_LANGUAGE_CODE:
                $datasetArray = $this->russianLanguageService->fetchAllNamesWithoutUniquePatternCheck($limit);
                $service = $this->russianLanguageService;
                break;
            case LanguageServicesAndCodes::SERBOCROATIAN_LANGUAGE_CODE:
                $datasetArray = $this->serboCroatianLanguageService->fetchAllNamesWithoutUniquePatternCheck($limit);
                $service = $this->serboCroatianLanguageService;
                break;
            case LanguageServicesAndCodes::SPANISH_LANGUAGE_CODE:
                $datasetArray = $this->spanishLanguageService->fetchAllNamesWithoutUniquePatternCheck($limit);
                $service = $this->spanishLanguageService;
                break;
            case LanguageServicesAndCodes::SWEDISH_LANGUAGE_CODE:
                $datasetArray = $this->swedishLanguageService->fetchAllNamesWithoutUniquePatternCheck($limit);
                $service = $this->swedishLanguageService;
                break;
            case LanguageServicesAndCodes::TAGALOG_LANGUAGE_CODE:
                $datasetArray = $this->tagalogLanguageService->fetchAllNamesWithoutUniquePatternCheck($limit);
                $service = $this->tagalogLanguageService;
                break;
            case LanguageServicesAndCodes::TURKISH_LANGUAGE_CODE:
                $datasetArray = $this->turkishLanguageService->fetchAllNamesWithoutUniquePatternCheck($limit);
                $service = $this->turkishLanguageService;
                break;
            case LanguageServicesAndCodes::UKRAINIAN_LANGUAGE_CODE:
                $datasetArray = $this->ukrainianLanguageService->fetchAllNamesWithoutUniquePatternCheck($limit);
                $service = $this->ukrainianLanguageService;
                break;
            case LanguageServicesAndCodes::ALBANIAN_LANGUAGE_CODE:
                $datasetArray = $this->albanianLanguageService->fetchAllNamesWithoutUniquePatternCheck($limit);
                $service = $this->albanianLanguageService;
                break;
            case LanguageServicesAndCodes::CZECH_LANGUAGE_CODE:
                $datasetArray = $this->czechLanguageService->fetchAllNamesWithoutUniquePatternCheck($limit);
                $service = $this->czechLanguageService;
                break;
            case LanguageServicesAndCodes::AFRIKAANS_LANGUAGE_CODE:
                $datasetArray = $this->afrikaansLanguageService->fetchAllNamesWithoutUniquePatternCheck($limit);
                $service = $this->afrikaansLanguageService;
                break;
            case LanguageServicesAndCodes::ARMENIAN_LANGUAGE_CODE:
                $datasetArray = $this->armenianLanguageService->fetchAllNamesWithoutUniquePatternCheck($limit);
                $service = $this->armenianLanguageService;
                break;
            case LanguageServicesAndCodes::AFAR_LANGUAGE_CODE:
                $datasetArray = $this->afarLanguageService->fetchAllNamesWithoutUniquePatternCheck($limit);
                $service = $this->afarLanguageService;
                break;
            case LanguageServicesAndCodes::BENGALI_LANGUAGE_CODE:
                $datasetArray = $this->bengaliLanguageService->fetchAllNamesWithoutUniquePatternCheck($limit);
                $service = $this->bengaliLanguageService;
                break;
            case LanguageServicesAndCodes::UZBEK_LANGUAGE_CODE:
                $datasetArray = $this->uzbekLanguageService->fetchAllNamesWithoutUniquePatternCheck($limit);
                $service = $this->uzbekLanguageService;
                break;
            default:
                $acceptedLangCodes = implode(', ', LanguageServicesAndCodes::getLanguageCodes());
                $output->writeln('<error>No language provided in --lang parameter or language is not accepted. 
                Accepted language params are: '.$acceptedLangCodes.' </error>');
                return Command::FAILURE;
        }

        if (!$datasetArray) {
            $output->writeln('<error>No valid data found for pattern calculation.</error>');
            return Command::FAILURE;
        }

        foreach ($datasetArray as $datasetRow) {
            $word = $datasetRow['name'];
            $wordEntity = $service->fetchOneByName($word);

            $sequence = $this->getSequence($word);

            $wordEntity->setUniquePatternCheck(date('Y-m-d H:i:s'));

            if (!$this->checkSequenceAgainstOtherLanguages($sequence, $lang)) {
                $existing = $this->uniquePatternRepository->findOneBy([
                    'pattern' => $sequence,
                    'languageCode' => $lang,
                ]);

                if (!$existing) {
                    $uniquePatternEntity = new UniquePatternEntity();
                    $uniquePatternEntity->setPattern($sequence)
                        ->setPosition(self::SEQUENCE_POSITION)
                        ->setCount(self::SEQUENCE_COUNT)
                        ->setLanguageCode($lang);

                    $this->uniquePatternRepository->add($uniquePatternEntity);
                }
            }

        }

        return Command::SUCCESS;
    }

    protected function checkSequenceAgainstOtherLanguages(string $sequence, string $currentLang): bool
    {
        $result = false;

        foreach ($this->languageServiceMap as $langCode => $languageService) {
            if ($langCode === $currentLang) {
                continue;
            }

            $result = $languageService->findStartingByName($sequence);
            if ($result) {
                return $result;
            }
        }

        return $result;
    }

    protected function getSequence(string $word, int $count = self::SEQUENCE_COUNT): string
    {
        return mb_substr($word, 0, $count);
    }

}