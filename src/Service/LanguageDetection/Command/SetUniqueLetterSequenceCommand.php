<?php

namespace App\Service\LanguageDetection\Command;

use App\Entity\UniquePatternEntity;
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
use Doctrine\ORM\EntityManagerInterface;
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
    private const int BATCH_SIZE = 1000;

    private array $languageServiceMap = [];
    private array $processedSequences = [];

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
        protected EntityManagerInterface $entityManager,
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
        $lang = $input->getOption('lang');

        if (!array_key_exists($lang, $this->languageServiceMap)) {
            $acceptedLangCodes = implode(', ', LanguageDetectionService::getLanguageCodes());
            $output->writeln('<error>No language provided in --lang parameter or language is not accepted. 
                Accepted language params are: '.$acceptedLangCodes.' </error>');
            return Command::FAILURE;
        }

        $languageService = $this->languageServiceMap[$lang];

        $datasetIterator = $this->getDatasetIterator($languageService);

        if (!$datasetIterator) {
            $output->writeln('<error>No valid data found for pattern calculation.</error>');
            return Command::FAILURE;
        }

        $batchCount = 0;
        $totalProcessed = 0;
        $uniqueSequences = [];

        foreach ($datasetIterator as $datasetRow) {
            $word = $datasetRow['name'] ?? '';

            if (empty($word)) {
                continue;
            }

            $sequence = $this->getSequence($word);

            if (isset($this->processedSequences[$sequence])) {
                continue;
            }

            $this->processedSequences[$sequence] = true;

            if (!$this->checkSequenceAgainstOtherLanguages($sequence, $lang)) {
                $uniqueSequences[] = $sequence;
                $batchCount++;

                if ($batchCount >= self::BATCH_SIZE) {
                    $this->processBatch($uniqueSequences, $lang);
                    $uniqueSequences = [];
                    $batchCount = 0;

                    $this->clearMemory();
                }
            }

            $totalProcessed++;

            if ($totalProcessed % 5000 === 0) {
                $output->writeln("Processed {$totalProcessed} records...");
                $this->clearMemory();
            }

            unset($datasetRow, $word, $sequence);
        }

        if (!empty($uniqueSequences)) {
            $this->processBatch($uniqueSequences, $lang);
        }

        $this->entityManager->flush();
        $output->writeln("Successfully processed {$totalProcessed} records for language: {$lang}");

        return Command::SUCCESS;
    }

    private function getDatasetIterator($languageService): ?\Iterator
    {
        if (method_exists($languageService, 'fetchNamesIterator')) {
            return $languageService->fetchNamesIterator();
        }

        $datasetArray = $languageService->fetchAllNames();
        if (!$datasetArray) {
            return null;
        }

        return $this->arrayToGenerator($datasetArray);
    }

    private function arrayToGenerator(array $data): \Generator
    {
        foreach ($data as $item) {
            yield $item;
            unset($item);
        }

        unset($data);
    }

    private function processBatch(array $sequences, string $lang): void
    {
        foreach ($sequences as $sequence) {
            $uniquePatternEntity = new UniquePatternEntity();
            $uniquePatternEntity->setPattern($sequence)
                ->setPosition(self::SEQUENCE_POSITION)
                ->setCount(self::SEQUENCE_COUNT)
                ->setLanguageCode($lang);

            $this->entityManager->persist($uniquePatternEntity);
        }

        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    private function clearMemory(): void
    {
        $this->entityManager->clear();

        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }

        if (count($this->processedSequences) > 100000) {
            $this->processedSequences = [];
        }
    }

    protected function checkSequenceAgainstOtherLanguages(string $sequence, string $currentLang): bool
    {
        foreach ($this->languageServiceMap as $langCode => $languageService) {
            if ($langCode === $currentLang) {
                continue;
            }

            if ($languageService->findStartingByName($sequence)) {
                return true;
            }
        }

        return false;
    }

    protected function getSequence(string $word, int $count = self::SEQUENCE_COUNT): string
    {
        return mb_substr($word, 0, $count);
    }
}