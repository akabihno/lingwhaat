<?php

namespace App\Service\LanguageDetection\Command;

use App\Entity\UniquePatternEntity;
use App\Repository\AfrikaansLanguageRepository;
use App\Repository\AlbanianLanguageRepository;
use App\Repository\ArmenianLanguageRepository;
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
use App\Repository\LatinLanguageRepository;
use App\Repository\LatvianLanguageRepository;
use App\Repository\LithuanianLanguageRepository;
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
use App\Repository\UniquePatternRepository;
use App\Service\LanguageDetection\LanguageDetectionService;
use App\Service\LanguageDetection\LanguageServices\AfrikaansLanguageService;
use App\Service\LanguageDetection\LanguageServices\AlbanianLanguageService;
use App\Service\LanguageDetection\LanguageServices\ArmenianLanguageService;
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
        protected UniquePatternRepository $uniquePatternRepository,
    ) {
        parent::__construct();

        $this->languageServiceMap = [
            LanguageDetectionService::DUTCH_LANGUAGE_CODE => $this->dutchLanguageService,
            LanguageDetectionService::ENGLISH_LANGUAGE_CODE => $this->englishLanguageService,
            LanguageDetectionService::ESTONIAN_LANGUAGE_CODE => $this->estonianLanguageService,
            LanguageDetectionService::FRENCH_LANGUAGE_CODE => $this->frenchLanguageService,
            LanguageDetectionService::GEORGIAN_LANGUAGE_CODE => $this->georgianLanguageService,
            LanguageDetectionService::GERMAN_LANGUAGE_CODE => $this->germanLanguageService,
            LanguageDetectionService::GREEK_LANGUAGE_CODE => $this->greekLanguageService,
            LanguageDetectionService::HINDI_LANGUAGE_CODE => $this->hindiLanguageService,
            LanguageDetectionService::ITALIAN_LANGUAGE_CODE => $this->italianLanguageService,
            LanguageDetectionService::LATIN_LANGUAGE_CODE => $this->latinLanguageService,
            LanguageDetectionService::LATVIAN_LANGUAGE_CODE => $this->latvianLanguageService,
            LanguageDetectionService::LITHUANIAN_LANGUAGE_CODE => $this->lithuanianLanguageService,
            LanguageDetectionService::POLISH_LANGUAGE_CODE => $this->polishLanguageService,
            LanguageDetectionService::PORTUGUESE_LANGUAGE_CODE => $this->portugueseLanguageService,
            LanguageDetectionService::ROMANIAN_LANGUAGE_CODE => $this->romanianLanguageService,
            LanguageDetectionService::RUSSIAN_LANGUAGE_CODE => $this->russianLanguageService,
            LanguageDetectionService::SERBOCROATIAN_LANGUAGE_CODE => $this->serboCroatianLanguageService,
            LanguageDetectionService::SPANISH_LANGUAGE_CODE => $this->spanishLanguageService,
            LanguageDetectionService::SWEDISH_LANGUAGE_CODE => $this->swedishLanguageService,
            LanguageDetectionService::TAGALOG_LANGUAGE_CODE => $this->tagalogLanguageService,
            LanguageDetectionService::TURKISH_LANGUAGE_CODE => $this->turkishLanguageService,
            LanguageDetectionService::UKRAINIAN_LANGUAGE_CODE => $this->ukrainianLanguageService,
            LanguageDetectionService::ALBANIAN_LANGUAGE_CODE => $this->albanianLanguageService,
            LanguageDetectionService::CZECH_LANGUAGE_CODE => $this->czechLanguageService,
            LanguageDetectionService::AFRIKAANS_LANGUAGE_CODE => $this->afrikaansLanguageService,
            LanguageDetectionService::ARMENIAN_LANGUAGE_CODE => $this->armenianLanguageService,
        ];
    }
    protected function configure(): void
    {
        $this
            ->setDescription('Calculate and store unique letter sequence for a specific language')
            ->addOption('lang', 'l', InputOption::VALUE_REQUIRED,
                'Language code in: ' . implode(', ', LanguageDetectionService::getLanguageCodes())
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // example: php bin/console language:sequence:set --lang lv

        $lang = $input->getOption('lang');

        switch ($lang) {
            case LanguageDetectionService::DUTCH_LANGUAGE_CODE:
                $datasetArray = $this->dutchLanguageService->fetchAllNamesWithoutUniquePatternCheck(self::WORDS_LIMIT);
                $service = $this->dutchLanguageService;
                break;
            case LanguageDetectionService::ENGLISH_LANGUAGE_CODE:
                $datasetArray = $this->englishLanguageService->fetchAllNamesWithoutUniquePatternCheck(self::WORDS_LIMIT);
                $service = $this->englishLanguageService;
                break;
            case LanguageDetectionService::ESTONIAN_LANGUAGE_CODE:
                $datasetArray = $this->estonianLanguageService->fetchAllNamesWithoutUniquePatternCheck(self::WORDS_LIMIT);
                $service = $this->estonianLanguageService;
                break;
            case LanguageDetectionService::FRENCH_LANGUAGE_CODE:
                $datasetArray = $this->frenchLanguageService->fetchAllNamesWithoutUniquePatternCheck(self::WORDS_LIMIT);
                $service = $this->frenchLanguageService;
                break;
            case LanguageDetectionService::GEORGIAN_LANGUAGE_CODE:
                $datasetArray = $this->georgianLanguageService->fetchAllNamesWithoutUniquePatternCheck(self::WORDS_LIMIT);
                $service = $this->georgianLanguageService;
                break;
            case LanguageDetectionService::GERMAN_LANGUAGE_CODE:
                $datasetArray = $this->germanLanguageService->fetchAllNamesWithoutUniquePatternCheck(self::WORDS_LIMIT);
                $service = $this->germanLanguageService;
                break;
            case LanguageDetectionService::GREEK_LANGUAGE_CODE:
                $datasetArray = $this->greekLanguageService->fetchAllNamesWithoutUniquePatternCheck(self::WORDS_LIMIT);
                $service = $this->greekLanguageService;
                break;
            case LanguageDetectionService::HINDI_LANGUAGE_CODE:
                $datasetArray = $this->hindiLanguageService->fetchAllNamesWithoutUniquePatternCheck(self::WORDS_LIMIT);
                $service = $this->hindiLanguageService;
                break;
            case LanguageDetectionService::ITALIAN_LANGUAGE_CODE:
                $datasetArray = $this->italianLanguageService->fetchAllNamesWithoutUniquePatternCheck(self::WORDS_LIMIT);
                $service = $this->italianLanguageService;
                break;
            case LanguageDetectionService::LATIN_LANGUAGE_CODE:
                $datasetArray = $this->latinLanguageService->fetchAllNamesWithoutUniquePatternCheck(self::WORDS_LIMIT);
                $service = $this->latinLanguageService;
                break;
            case LanguageDetectionService::LATVIAN_LANGUAGE_CODE:
                $datasetArray = $this->latvianLanguageService->fetchAllNamesWithoutUniquePatternCheck(self::WORDS_LIMIT);
                $service = $this->latvianLanguageService;
                break;
            case LanguageDetectionService::LITHUANIAN_LANGUAGE_CODE:
                $datasetArray = $this->lithuanianLanguageService->fetchAllNamesWithoutUniquePatternCheck(self::WORDS_LIMIT);
                $service = $this->lithuanianLanguageService;
                break;
            case LanguageDetectionService::POLISH_LANGUAGE_CODE:
                $datasetArray = $this->polishLanguageService->fetchAllNamesWithoutUniquePatternCheck(self::WORDS_LIMIT);
                $service = $this->polishLanguageService;
                break;
            case LanguageDetectionService::PORTUGUESE_LANGUAGE_CODE:
                $datasetArray = $this->portugueseLanguageService->fetchAllNamesWithoutUniquePatternCheck(self::WORDS_LIMIT);
                $service = $this->portugueseLanguageService;
                break;
            case LanguageDetectionService::ROMANIAN_LANGUAGE_CODE:
                $datasetArray = $this->romanianLanguageService->fetchAllNamesWithoutUniquePatternCheck(self::WORDS_LIMIT);
                $service = $this->romanianLanguageService;
                break;
            case LanguageDetectionService::RUSSIAN_LANGUAGE_CODE:
                $datasetArray = $this->russianLanguageService->fetchAllNamesWithoutUniquePatternCheck(self::WORDS_LIMIT);
                $service = $this->russianLanguageService;
                break;
            case LanguageDetectionService::SERBOCROATIAN_LANGUAGE_CODE:
                $datasetArray = $this->serboCroatianLanguageService->fetchAllNamesWithoutUniquePatternCheck(self::WORDS_LIMIT);
                $service = $this->serboCroatianLanguageService;
                break;
            case LanguageDetectionService::SPANISH_LANGUAGE_CODE:
                $datasetArray = $this->spanishLanguageService->fetchAllNamesWithoutUniquePatternCheck(self::WORDS_LIMIT);
                $service = $this->spanishLanguageService;
                break;
            case LanguageDetectionService::SWEDISH_LANGUAGE_CODE:
                $datasetArray = $this->swedishLanguageService->fetchAllNamesWithoutUniquePatternCheck(self::WORDS_LIMIT);
                $service = $this->swedishLanguageService;
                break;
            case LanguageDetectionService::TAGALOG_LANGUAGE_CODE:
                $datasetArray = $this->tagalogLanguageService->fetchAllNamesWithoutUniquePatternCheck(self::WORDS_LIMIT);
                $service = $this->tagalogLanguageService;
                break;
            case LanguageDetectionService::TURKISH_LANGUAGE_CODE:
                $datasetArray = $this->turkishLanguageService->fetchAllNamesWithoutUniquePatternCheck(self::WORDS_LIMIT);
                $service = $this->turkishLanguageService;
                break;
            case LanguageDetectionService::UKRAINIAN_LANGUAGE_CODE:
                $datasetArray = $this->ukrainianLanguageService->fetchAllNamesWithoutUniquePatternCheck(self::WORDS_LIMIT);
                $service = $this->ukrainianLanguageService;
                break;
            case LanguageDetectionService::ALBANIAN_LANGUAGE_CODE:
                $datasetArray = $this->albanianLanguageService->fetchAllNamesWithoutUniquePatternCheck(self::WORDS_LIMIT);
                $service = $this->albanianLanguageService;
                break;
            case LanguageDetectionService::CZECH_LANGUAGE_CODE:
                $datasetArray = $this->czechLanguageService->fetchAllNamesWithoutUniquePatternCheck(self::WORDS_LIMIT);
                $service = $this->czechLanguageService;
                break;
            case LanguageDetectionService::AFRIKAANS_LANGUAGE_CODE:
                $datasetArray = $this->afrikaansLanguageService->fetchAllNamesWithoutUniquePatternCheck(self::WORDS_LIMIT);
                $service = $this->afrikaansLanguageService;
                break;
            case LanguageDetectionService::ARMENIAN_LANGUAGE_CODE:
                $datasetArray = $this->armenianLanguageService->fetchAllNamesWithoutUniquePatternCheck(self::WORDS_LIMIT);
                $service = $this->armenianLanguageService;
                break;
            default:
                $acceptedLangCodes = implode(', ', LanguageDetectionService::getLanguageCodes());
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

            if (!$this->checkSequenceAgainstOtherLanguages($sequence, $lang)) {
                $uniquePatternEntity = new UniquePatternEntity();
                $uniquePatternEntity->setPattern($sequence)
                    ->setPosition(self::SEQUENCE_POSITION)
                    ->setCount(self::SEQUENCE_COUNT)
                    ->setLanguageCode($lang);

                $wordEntity->setUniquePatternCheck(date('Y-m-d H:i:s'));

                $this->uniquePatternRepository->add($uniquePatternEntity);
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