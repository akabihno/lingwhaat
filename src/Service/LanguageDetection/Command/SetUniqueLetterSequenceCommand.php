<?php

namespace App\Service\LanguageDetection\Command;

use App\Entity\UniquePatternEntity;
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
                $datasetArray = $this->dutchLanguageService->fetchAllNames();
                break;
            case LanguageDetectionService::ENGLISH_LANGUAGE_CODE:
                $datasetArray = $this->englishLanguageService->fetchAllNames();
                break;
            case LanguageDetectionService::ESTONIAN_LANGUAGE_CODE:
                $datasetArray = $this->estonianLanguageService->fetchAllNames();
                break;
            case LanguageDetectionService::FRENCH_LANGUAGE_CODE:
                $datasetArray = $this->frenchLanguageService->fetchAllNames();
                break;
            case LanguageDetectionService::GEORGIAN_LANGUAGE_CODE:
                $datasetArray = $this->georgianLanguageService->fetchAllNames();
                break;
            case LanguageDetectionService::GERMAN_LANGUAGE_CODE:
                $datasetArray = $this->germanLanguageService->fetchAllNames();
                break;
            case LanguageDetectionService::GREEK_LANGUAGE_CODE:
                $datasetArray = $this->greekLanguageService->fetchAllNames();
                break;
            case LanguageDetectionService::HINDI_LANGUAGE_CODE:
                $datasetArray = $this->hindiLanguageService->fetchAllNames();
                break;
            case LanguageDetectionService::ITALIAN_LANGUAGE_CODE:
                $datasetArray = $this->italianLanguageService->fetchAllNames();
                break;
            case LanguageDetectionService::LATIN_LANGUAGE_CODE:
                $datasetArray = $this->latinLanguageService->fetchAllNames();
                break;
            case LanguageDetectionService::LATVIAN_LANGUAGE_CODE:
                $datasetArray = $this->latvianLanguageService->fetchAllNames();
                break;
            case LanguageDetectionService::LITHUANIAN_LANGUAGE_CODE:
                $datasetArray = $this->lithuanianLanguageService->fetchAllNames();
                break;
            case LanguageDetectionService::POLISH_LANGUAGE_CODE:
                $datasetArray = $this->polishLanguageService->fetchAllNames();
                break;
            case LanguageDetectionService::PORTUGUESE_LANGUAGE_CODE:
                $datasetArray = $this->portugueseLanguageService->fetchAllNames();
                break;
            case LanguageDetectionService::ROMANIAN_LANGUAGE_CODE:
                $datasetArray = $this->romanianLanguageService->fetchAllNames();
                break;
            case LanguageDetectionService::RUSSIAN_LANGUAGE_CODE:
                $datasetArray = $this->russianLanguageService->fetchAllNames();
                break;
            case LanguageDetectionService::SERBOCROATIAN_LANGUAGE_CODE:
                $datasetArray = $this->serboCroatianLanguageService->fetchAllNames();
                break;
            case LanguageDetectionService::SPANISH_LANGUAGE_CODE:
                $datasetArray = $this->spanishLanguageService->fetchAllNames();
                break;
            case LanguageDetectionService::SWEDISH_LANGUAGE_CODE:
                $datasetArray = $this->swedishLanguageService->fetchAllNames();
                break;
            case LanguageDetectionService::TAGALOG_LANGUAGE_CODE:
                $datasetArray = $this->tagalogLanguageService->fetchAllNames();
                break;
            case LanguageDetectionService::TURKISH_LANGUAGE_CODE:
                $datasetArray = $this->turkishLanguageService->fetchAllNames();
                break;
            case LanguageDetectionService::UKRAINIAN_LANGUAGE_CODE:
                $datasetArray = $this->ukrainianLanguageService->fetchAllNames();
                break;
            case LanguageDetectionService::ALBANIAN_LANGUAGE_CODE:
                $datasetArray = $this->albanianLanguageService->fetchAllNames();
                break;
            case LanguageDetectionService::CZECH_LANGUAGE_CODE:
                $datasetArray = $this->czechLanguageService->fetchAllNames();
                break;
            case LanguageDetectionService::AFRIKAANS_LANGUAGE_CODE:
                $datasetArray = $this->afrikaansLanguageService->fetchAllNames();
                break;
            case LanguageDetectionService::ARMENIAN_LANGUAGE_CODE:
                $datasetArray = $this->armenianLanguageService->fetchAllNames();
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
            $sequence = $this->getSequence($word);

            if (!$this->checkSequenceAgainstOtherLanguages($sequence, $lang)) {
                $uniquePatternEntity = new UniquePatternEntity();
                $uniquePatternEntity->setPattern($sequence)
                    ->setPosition(self::SEQUENCE_POSITION)
                    ->setCount(self::SEQUENCE_COUNT)
                    ->setLanguageCode($lang);
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