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
use App\Service\LanguageNormalizationService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class TransliterationDetectionService
{
    public function __construct(
        protected LoggerInterface $logger,
        protected LanguageNormalizationService $languageNormalizationService,
        protected UseIpaPredictorModelCommand $ipaPredictorModelCommand,
        protected UseWordPredictorModelCommand $wordPredictorModelCommand,
        protected FrenchLanguageService $frenchLanguageService,
        protected GermanLanguageService $germanLanguageService,
        protected GreekLanguageService $greekLanguageService,
        protected ItalianLanguageService $italianLanguageService,
        protected LatvianLanguageService $latvianLanguageService,
        protected LithuanianLanguageService $lithuanianLanguageService,
        protected PolishLanguageService $polishLanguageService,
        protected PortugueseLanguageService $portugueseLanguageService,
        protected RomanianLanguageService $romanianLanguageService,
        protected RussianLanguageService $russianLanguageService,
        protected SerboCroatianLanguageService $serboCroatianLanguageService,
        protected TagalogLanguageService $tagalogLanguageService,
        protected UkrainianLanguageService $ukrainianLanguageService,
        protected EsuLanguageService $esuLanguageService,
        protected SpanishLanguageService $spanishLanguageService,
        protected LatinLanguageService $latinLanguageService,
        protected SwedishLanguageService $swedishLanguageService,
        protected EstonianLanguageService $estonianLanguageService,
        protected EnglishLanguageService $englishLanguageService,
        protected DutchLanguageService $dutchLanguageService,
        protected HindiLanguageService $hindiLanguageService,
        protected GeorgianLanguageService $georgianLanguageService,
        protected TurkishLanguageService $turkishLanguageService,
    )
    {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function run(array $words, string $uuidStr, float $start): array
    {
        $result = [];
        $languageCounts = [];

        foreach (LanguageDetectionService::getLanguageCodes() as $srcLanguageCode) {
            foreach (LanguageDetectionService::getLanguageCodes() as $dstLanguageCode) {
                foreach ($words as $word) {
                    $wordPredictedIpa = $this->executeIpaPredictor($srcLanguageCode, $word);
                    $predictedTargetWord = $this->executeWordPredictor($dstLanguageCode, $wordPredictedIpa);

                    $languageCounts[$word][$srcLanguageCode][$dstLanguageCode] = $this->checkLanguage($dstLanguageCode, $predictedTargetWord);
                }
            }
        }

        $this->logLanguageDetectionResult($uuidStr, $languageCounts);

        $finish = microtime(true);

        return [
            'language' => 'Test',
            'code' => 'tt',
            'input' => 'input',
            'count' => 0,
            'matches' => 0,
            'time' => $finish - $start
        ];

    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function executeIpaPredictor(string $lang, string $word): string
    {
        $input = new ArrayInput([
            '--lang' => $lang,
            '--word' => $word,
        ]);

        $output = new BufferedOutput();

        $this->ipaPredictorModelCommand->execute($input, $output);

        return $output->fetch();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function executeWordPredictor(string $lang, string $ipa): string
    {
        $input = new ArrayInput([
            '--lang' => $lang,
            '--ipa' => $ipa,
        ]);

        $output = new BufferedOutput();

        $this->wordPredictorModelCommand->execute($input, $output);

        return $output->fetch();
    }

    protected function getWordEntry(string $uuidStr, string $language, string $code): array
    {
        $result = ['language' => $language, 'code' => $code];
        $this->logLanguageDetectionResult($uuidStr, $result);

        return $result;
    }

    protected function logLanguageDetectionResult(string $uuidStr, array $result): void
    {
        $this->logger->info(sprintf('[TransliterationDetectionService][%s] %s', $uuidStr, json_encode($result)));
    }

    protected function checkLanguage(string $languageCode, string $word): bool
    {
        switch ($languageCode) {
            case LanguageDetectionService::FRENCH_LANGUAGE_CODE:
                return $this->checkFrenchLanguage($word);
            case LanguageDetectionService::GERMAN_LANGUAGE_CODE:
                return $this->checkGermanLanguage($word);
            case LanguageDetectionService::GREEK_LANGUAGE_CODE:
                return $this->checkGreekLanguage($word);
            case LanguageDetectionService::ITALIAN_LANGUAGE_CODE:
                return $this->checkItalianLanguage($word);
            case LanguageDetectionService::LATVIAN_LANGUAGE_CODE:
                return $this->checkLatvianLanguage($word);
            case LanguageDetectionService::LITHUANIAN_LANGUAGE_CODE:
                return $this->checkLithuanianLanguage($word);
            case LanguageDetectionService::POLISH_LANGUAGE_CODE:
                return $this->checkPolishLanguage($word);
            case LanguageDetectionService::PORTUGUESE_LANGUAGE_CODE:
                return $this->checkPortugueseLanguage($word);
            case LanguageDetectionService::ROMANIAN_LANGUAGE_CODE:
                return $this->checkRomanianLanguage($word);
            case LanguageDetectionService::RUSSIAN_LANGUAGE_CODE:
                return $this->checkRussianLanguage($word);
            case LanguageDetectionService::SERBOCROATIAN_LANGUAGE_CODE:
                return $this->checkSerboCroatianLanguage($word);
            case LanguageDetectionService::TAGALOG_LANGUAGE_CODE:
                return $this->checkTagalogLanguage($word);
            case LanguageDetectionService::UKRAINIAN_LANGUAGE_CODE:
                return $this->checkUkrainianLanguage($word);
            case LanguageDetectionService::SPANISH_LANGUAGE_CODE:
                return $this->checkSpanishLanguage($word);
            case LanguageDetectionService::LATIN_LANGUAGE_CODE:
                return $this->checkLatinLanguage($word);
            case LanguageDetectionService::SWEDISH_LANGUAGE_CODE:
                return $this->checkSwedishLanguage($word);
            case LanguageDetectionService::ESTONIAN_LANGUAGE_CODE:
                return $this->checkEstonianLanguage($word);
            case LanguageDetectionService::ENGLISH_LANGUAGE_CODE:
                return $this->checkEnglishLanguage($word);
            case LanguageDetectionService::DUTCH_LANGUAGE_CODE:
                return $this->checkDutchLanguage($word);
            case LanguageDetectionService::HINDI_LANGUAGE_CODE:
                return $this->checkHindiLanguage($word);
            case LanguageDetectionService::GEORGIAN_LANGUAGE_CODE:
                return $this->checkGeorgianLanguage($word);
            case LanguageDetectionService::TURKISH_LANGUAGE_CODE:
                return $this->checkTurkishLanguage($word);
        }

        return false;
    }

    protected function checkFrenchLanguage(string $word): bool
    {
        return $this->frenchLanguageService->findApproximateByName($word);
    }

    protected function checkGermanLanguage(string $word): bool
    {
        return $this->germanLanguageService->findApproximateByName($word);
    }

    protected function checkGreekLanguage(string $word): bool
    {
        return $this->greekLanguageService->findApproximateByName($word);
    }

    protected function checkItalianLanguage(string $word): bool
    {
        return $this->italianLanguageService->findApproximateByName($word);
    }

    protected function checkLatvianLanguage(string $word): bool
    {
        return $this->latvianLanguageService->findApproximateByName($word);
    }

    protected function checkLithuanianLanguage(string $word): bool
    {
        return $this->lithuanianLanguageService->findApproximateByName($word);
    }

    protected function checkPolishLanguage(string $word): bool
    {
        return $this->polishLanguageService->findApproximateByName($word);
    }

    protected function checkPortugueseLanguage(string $word): bool
    {
        return $this->portugueseLanguageService->findApproximateByName($word);
    }

    protected function checkRomanianLanguage(string $word): bool
    {
        return $this->romanianLanguageService->findApproximateByName($word);
    }

    protected function checkRussianLanguage(string $word): bool
    {
        return $this->russianLanguageService->findApproximateByName($word);
    }

    protected function checkSerboCroatianLanguage(string $word): bool
    {
        return $this->serboCroatianLanguageService->findApproximateByName($word);
    }

    protected function checkTagalogLanguage(string $word): bool
    {
        return $this->tagalogLanguageService->findApproximateByName($word);
    }

    protected function checkUkrainianLanguage(string $word): bool
    {
        return $this->ukrainianLanguageService->findApproximateByName($word);
    }

    protected function checkSpanishLanguage(string $word): bool
    {
        return $this->spanishLanguageService->findApproximateByName($word);
    }

    protected function checkLatinLanguage(string $word): bool
    {
        return $this->latinLanguageService->findApproximateByName($word);
    }

    protected function checkSwedishLanguage(string $word): bool
    {
        return $this->swedishLanguageService->findApproximateByName($word);
    }

    protected function checkEstonianLanguage(string $word): bool
    {
        return $this->estonianLanguageService->findApproximateByName($word);
    }

    protected function checkEnglishLanguage(string $word): bool
    {
        return $this->englishLanguageService->findApproximateByName($word);
    }

    protected function checkDutchLanguage(string $word): bool
    {
        return $this->dutchLanguageService->findApproximateByName($word);
    }

    protected function checkHindiLanguage(string $word): bool
    {
        return $this->hindiLanguageService->findApproximateByName($word);
    }

    protected function checkGeorgianLanguage(string $word): bool
    {
        return $this->georgianLanguageService->findApproximateByName($word);
    }

    protected function checkTurkishLanguage(string $word): bool
    {
        return $this->turkishLanguageService->findApproximateByName($word);
    }

}