<?php

namespace App\Service\LanguageDetection;

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
use App\Service\LanguageDetection\LanguageTransliteration\TransliterationDetectionService;
use App\Service\LanguageNormalizationService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class LanguageDetectionService
{
    const string FRENCH_LANGUAGE_NAME = 'French';
    const string FRENCH_LANGUAGE_CODE = 'fr';
    const string GERMAN_LANGUAGE_NAME = 'German';
    const string GERMAN_LANGUAGE_CODE = 'de';
    const string GREEK_LANGUAGE_NAME = 'Greek';
    const string GREEK_LANGUAGE_CODE = 'el';
    const string ITALIAN_LANGUAGE_NAME = 'Italian';
    const string ITALIAN_LANGUAGE_CODE = 'it';
    const string LATVIAN_LANGUAGE_NAME = 'Latvian';
    const string LATVIAN_LANGUAGE_CODE = 'lv';
    const string LITHUANIAN_LANGUAGE_NAME = 'Lithuanian';
    const string LITHUANIAN_LANGUAGE_CODE = 'lt';
    const string POLISH_LANGUAGE_NAME = 'Polish';
    const string POLISH_LANGUAGE_CODE = 'pl';
    const string PORTUGUESE_LANGUAGE_NAME = 'Portuguese';
    const string PORTUGUESE_LANGUAGE_CODE = 'pt';
    const string ROMANIAN_LANGUAGE_NAME = 'Romanian';
    const string ROMANIAN_LANGUAGE_CODE = 'ro';
    const string RUSSIAN_LANGUAGE_NAME = 'Russian';
    const string RUSSIAN_LANGUAGE_CODE = 'ru';
    const string SERBOCROATIAN_LANGUAGE_NAME = 'Serbo-Croatian';
    const string SERBOCROATIAN_LANGUAGE_CODE = 'sh';
    const string TAGALOG_LANGUAGE_NAME = 'Tagalog';
    const string TAGALOG_LANGUAGE_CODE = 'tl';
    const string UKRAINIAN_LANGUAGE_NAME = 'Ukrainian';
    const string UKRAINIAN_LANGUAGE_CODE = 'uk';
    const string SPANISH_LANGUAGE_NAME = 'Spanish';
    const string SPANISH_LANGUAGE_CODE = 'es';
    const string LATIN_LANGUAGE_NAME = 'Latin';
    const string LATIN_LANGUAGE_CODE = 'la';
    const string SWEDISH_LANGUAGE_NAME = 'Swedish';
    const string SWEDISH_LANGUAGE_CODE = 'sv';
    const string ESTONIAN_LANGUAGE_NAME = 'Estonian';
    const string ESTONIAN_LANGUAGE_CODE = 'et';
    const string ENGLISH_LANGUAGE_NAME = 'English';
    const string ENGLISH_LANGUAGE_CODE = 'en';
    const string DUTCH_LANGUAGE_NAME = 'Dutch';
    const string DUTCH_LANGUAGE_CODE = 'nl';
    const string HINDI_LANGUAGE_NAME = 'Hindi';
    const string HINDI_LANGUAGE_CODE = 'hi';
    const string GEORGIAN_LANGUAGE_NAME = 'Georgian';
    const string GEORGIAN_LANGUAGE_CODE = 'ka';
    const string TURKISH_LANGUAGE_NAME = 'Turkish';
    const string TURKISH_LANGUAGE_CODE = 'tr';
    const string ALBANIAN_LANGUAGE_NAME = 'Albanian';
    const string ALBANIAN_LANGUAGE_CODE = 'sq';
    const string CZECH_LANGUAGE_NAME = 'Czech';
    const string CZECH_LANGUAGE_CODE = 'cs';
    const string AFRIKAANS_LANGUAGE_NAME = 'Afrikaans';
    const string AFRIKAANS_LANGUAGE_CODE = 'af';
    const string ARMENIAN_LANGUAGE_NAME = 'Armenian';
    const string ARMENIAN_LANGUAGE_CODE = 'hy';

    const string LANGUAGE_NOT_FOUND = 'Language not found';
    public function __construct(
        protected LoggerInterface $logger,
        protected LanguageNormalizationService $languageNormalizationService,
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
        protected SpanishLanguageService $spanishLanguageService,
        protected LatinLanguageService $latinLanguageService,
        protected SwedishLanguageService $swedishLanguageService,
        protected EstonianLanguageService $estonianLanguageService,
        protected EnglishLanguageService $englishLanguageService,
        protected DutchLanguageService $dutchLanguageService,
        protected HindiLanguageService $hindiLanguageService,
        protected GeorgianLanguageService $georgianLanguageService,
        protected TurkishLanguageService $turkishLanguageService,
        protected AlbanianLanguageService $albanianLanguageService,
        protected CzechLanguageService $czechLanguageService,
        protected AfrikaansLanguageService $afrikaansLanguageService,
        protected ArmenianLanguageService $armenianLanguageService,
        protected TransliterationDetectionService $transliterationDetectionService,
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
    public function process(string $languageInput, int $translitDetection): array
    {
        $uuid = Uuid::v4();
        $uuidStr = $uuid->toRfc4122();

        $language = self::LANGUAGE_NOT_FOUND;
        $code = null;
        $count = 0;
        $start = microtime(true);

        $result = [];
        $languageCounts = [];

        if ($languageInput) {
            $this->logger->info(sprintf('[LanguageDetectionService][%s] Processing language input: %s', $uuidStr, $languageInput));

            $normalizedInput = $this->languageNormalizationService->normalizeText($languageInput);
            $words = explode(' ', $normalizedInput);
            $words = $this->languageNormalizationService->removeArticles($words);
            $this->logger->info(sprintf('[LanguageDetectionService][%s] Processing normalized: %s', $uuidStr, json_encode($words)));

            $count = count($words);

            foreach ($words as $word) {
                $word = $this->languageNormalizationService->normalizeWord($word);
                $this->logger->info(sprintf('[LanguageDetectionService][%s] Processing word: %s', $uuidStr, $word));

                if ($this->checkEnglishLanguage($word)) {
                    $result[$word] = $this->getWordEntry($uuidStr, self::ENGLISH_LANGUAGE_NAME, self::ENGLISH_LANGUAGE_CODE);
                }
                if ($this->checkFrenchLanguage($word)) {
                    $result[$word] = $this->getWordEntry($uuidStr, self::FRENCH_LANGUAGE_NAME, self::FRENCH_LANGUAGE_CODE);
                }
                if ($this->checkGermanLanguage($word)) {
                    $result[$word] = $this->getWordEntry($uuidStr, self::GERMAN_LANGUAGE_NAME, self::GERMAN_LANGUAGE_CODE);
                }
                if ($this->checkGreekLanguage($word)) {
                    $result[$word] = $this->getWordEntry($uuidStr, self::GREEK_LANGUAGE_NAME, self::GREEK_LANGUAGE_CODE);
                }
                if ($this->checkItalianLanguage($word)) {
                    $result[$word] = $this->getWordEntry($uuidStr, self::ITALIAN_LANGUAGE_NAME, self::ITALIAN_LANGUAGE_CODE);
                }
                if ($this->checkLatvianLanguage($word)) {
                    $result[$word] = $this->getWordEntry($uuidStr, self::LATVIAN_LANGUAGE_NAME, self::LATVIAN_LANGUAGE_CODE);
                }
                if ($this->checkLithuanianLanguage($word)) {
                    $result[$word] = $this->getWordEntry($uuidStr, self::LITHUANIAN_LANGUAGE_NAME, self::LITHUANIAN_LANGUAGE_CODE);
                }
                if ($this->checkPolishLanguage($word)) {
                    $result[$word] = $this->getWordEntry($uuidStr, self::POLISH_LANGUAGE_NAME, self::POLISH_LANGUAGE_CODE);
                }
                if ($this->checkPortugueseLanguage($word)) {
                    $result[$word] = $this->getWordEntry($uuidStr, self::PORTUGUESE_LANGUAGE_NAME, self::PORTUGUESE_LANGUAGE_CODE);
                }
                if ($this->checkRomanianLanguage($word)) {
                    $result[$word] = $this->getWordEntry($uuidStr, self::ROMANIAN_LANGUAGE_NAME, self::ROMANIAN_LANGUAGE_CODE);
                }
                if ($this->checkRussianLanguage($word)) {
                    $result[$word] = $this->getWordEntry($uuidStr, self::RUSSIAN_LANGUAGE_NAME, self::RUSSIAN_LANGUAGE_CODE);
                }
                if ($this->checkSerboCroatianLanguage($word)) {
                    $result[$word] = $this->getWordEntry($uuidStr, self::SERBOCROATIAN_LANGUAGE_NAME, self::SERBOCROATIAN_LANGUAGE_CODE);
                }
                if ($this->checkTagalogLanguage($word)) {
                    $result[$word] = $this->getWordEntry($uuidStr, self::TAGALOG_LANGUAGE_NAME, self::TAGALOG_LANGUAGE_CODE);
                }
                if ($this->checkUkrainianLanguage($word)) {
                    $result[$word] = $this->getWordEntry($uuidStr, self::UKRAINIAN_LANGUAGE_NAME, self::UKRAINIAN_LANGUAGE_CODE);
                }
                if ($this->checkSpanishLanguage($word)) {
                    $result[$word] = $this->getWordEntry($uuidStr, self::SPANISH_LANGUAGE_NAME, self::SPANISH_LANGUAGE_CODE);
                }
                if ($this->checkLatinLanguage($word)) {
                    $result[$word] = $this->getWordEntry($uuidStr, self::LATIN_LANGUAGE_NAME, self::LATIN_LANGUAGE_CODE);
                }
                if ($this->checkSwedishLanguage($word)) {
                    $result[$word] = $this->getWordEntry($uuidStr, self::SWEDISH_LANGUAGE_NAME, self::SWEDISH_LANGUAGE_CODE);
                }
                if ($this->checkEstonianLanguage($word)) {
                    $result[$word] = $this->getWordEntry($uuidStr, self::ESTONIAN_LANGUAGE_NAME, self::ESTONIAN_LANGUAGE_CODE);
                }
                if ($this->checkDutchLanguage($word)) {
                    $result[$word] = $this->getWordEntry($uuidStr, self::DUTCH_LANGUAGE_NAME, self::DUTCH_LANGUAGE_CODE);
                }
                if ($this->checkHindiLanguage($word)) {
                    $result[$word] = $this->getWordEntry($uuidStr, self::HINDI_LANGUAGE_NAME, self::HINDI_LANGUAGE_CODE);
                }
                if ($this->checkGeorgianLanguage($word)) {
                    $result[$word] = $this->getWordEntry($uuidStr, self::GEORGIAN_LANGUAGE_NAME, self::GEORGIAN_LANGUAGE_CODE);
                }
                if ($this->checkTurkishLanguage($word)) {
                    $result[$word] = $this->getWordEntry($uuidStr, self::TURKISH_LANGUAGE_NAME, self::TURKISH_LANGUAGE_CODE);
                }
                if ($this->checkAlbanianLanguage($word)) {
                    $result[$word] = $this->getWordEntry($uuidStr, self::ALBANIAN_LANGUAGE_NAME, self::ALBANIAN_LANGUAGE_CODE);
                }
                if ($this->checkCzechLanguage($word)) {
                    $result[$word] = $this->getWordEntry($uuidStr, self::CZECH_LANGUAGE_NAME, self::CZECH_LANGUAGE_CODE);
                }
                if ($this->checkAfrikaansLanguage($word)) {
                    $result[$word] = $this->getWordEntry($uuidStr, self::AFRIKAANS_LANGUAGE_NAME, self::AFRIKAANS_LANGUAGE_CODE);
                }
                if ($this->checkArmenianLanguage($word)) {
                    $result[$word] = $this->getWordEntry($uuidStr, self::ARMENIAN_LANGUAGE_NAME, self::ARMENIAN_LANGUAGE_CODE);
                }

                if (isset($result[$word])) {
                    $langKey = $result[$word]['language'] . '|' . $result[$word]['code'];
                    if (!isset($languageCounts[$langKey])) {
                        $languageCounts[$langKey] = 0;
                    }
                    $languageCounts[$langKey]++;
                }

            }
        }

        if (!empty($languageCounts)) {
            arsort($languageCounts);
            $topEntry = array_key_first($languageCounts);
            [$language, $code] = explode('|', $topEntry);
            $matchCount = $languageCounts[$topEntry];
        } else {
            $matchCount = 0;
        }

        //TODO
        /*
        if ($language == self::LANGUAGE_NOT_FOUND && $translitDetection) {
            return $this->transliterationDetectionService->run($words, $uuidStr, $start);
        }
        */
        if ($translitDetection) {
            return $this->transliterationDetectionService->run($words, $uuidStr, $start);
        }

        $finish = microtime(true);

        return [
            'language' => $language,
            'code' => $code,
            'input' => $languageInput,
            'count' => $count,
            'matches' => $matchCount,
            'time' => $finish - $start
        ];
    }

    protected function getWordEntry(string $uuidStr, string $language, string $code): array
    {
        $result = ['language' => $language, 'code' => $code];
        $this->logLanguageDetectionResult($uuidStr, $result);

        return $result;
    }

    protected function logLanguageDetectionResult(string $uuidStr, array $result): void
    {
        $this->logger->info(sprintf('[LanguageDetectionService][%s] %s', $uuidStr, json_encode($result)));
    }

    protected function checkFrenchLanguage(string $word): bool
    {
        return $this->frenchLanguageService->checkLanguage($word);
    }

    protected function checkGermanLanguage(string $word): bool
    {
        return $this->germanLanguageService->checkLanguage($word);
    }

    protected function checkGreekLanguage(string $word): bool
    {
        return $this->greekLanguageService->checkLanguage($word);
    }

    protected function checkItalianLanguage(string $word): bool
    {
        return $this->italianLanguageService->checkLanguage($word);
    }

    protected function checkLatvianLanguage(string $word): bool
    {
        return $this->latvianLanguageService->checkLanguage($word);
    }

    protected function checkLithuanianLanguage(string $word): bool
    {
        return $this->lithuanianLanguageService->checkLanguage($word);
    }

    protected function checkPolishLanguage(string $word): bool
    {
        return $this->polishLanguageService->checkLanguage($word);
    }

    protected function checkPortugueseLanguage(string $word): bool
    {
        return $this->portugueseLanguageService->checkLanguage($word);
    }

    protected function checkRomanianLanguage(string $word): bool
    {
        return $this->romanianLanguageService->checkLanguage($word);
    }

    protected function checkRussianLanguage(string $word): bool
    {
        return $this->russianLanguageService->checkLanguage($word);
    }

    protected function checkSerboCroatianLanguage(string $word): bool
    {
        return $this->serboCroatianLanguageService->checkLanguage($word);
    }

    protected function checkTagalogLanguage(string $word): bool
    {
        return $this->tagalogLanguageService->checkLanguage($word);
    }

    protected function checkUkrainianLanguage(string $word): bool
    {
        return $this->ukrainianLanguageService->checkLanguage($word);
    }

    protected function checkSpanishLanguage(string $word): bool
    {
        return $this->spanishLanguageService->checkLanguage($word);
    }

    protected function checkLatinLanguage(string $word): bool
    {
        return $this->latinLanguageService->checkLanguage($word);
    }

    protected function checkSwedishLanguage(string $word): bool
    {
        return $this->swedishLanguageService->checkLanguage($word);
    }

    protected function checkEstonianLanguage(string $word): bool
    {
        return $this->estonianLanguageService->checkLanguage($word);
    }

    protected function checkEnglishLanguage(string $word): bool
    {
        return $this->englishLanguageService->checkLanguage($word);
    }

    protected function checkDutchLanguage(string $word): bool
    {
        return $this->dutchLanguageService->checkLanguage($word);
    }

    protected function checkHindiLanguage(string $word): bool
    {
        return $this->hindiLanguageService->checkLanguage($word);
    }

    protected function checkGeorgianLanguage(string $word): bool
    {
        return $this->georgianLanguageService->checkLanguage($word);
    }

    protected function checkTurkishLanguage(string $word): bool
    {
        return $this->turkishLanguageService->checkLanguage($word);
    }

    protected function checkAlbanianLanguage(string $word): bool
    {
        return $this->albanianLanguageService->checkLanguage($word);
    }

    protected function checkCzechLanguage(string $word): bool
    {
        return $this->czechLanguageService->checkLanguage($word);
    }

    protected function checkAfrikaansLanguage(string $word): bool
    {
        return $this->afrikaansLanguageService->checkLanguage($word);
    }

    protected function checkArmenianLanguage(string $word): bool
    {
        return $this->armenianLanguageService->checkLanguage($word);
    }

    public static function getLanguageCodes(): array
    {
        return [
            self::FRENCH_LANGUAGE_CODE,
            self::GERMAN_LANGUAGE_CODE,
            self::GREEK_LANGUAGE_CODE,
            self::ITALIAN_LANGUAGE_CODE,
            self::LATVIAN_LANGUAGE_CODE,
            self::LITHUANIAN_LANGUAGE_CODE,
            self::POLISH_LANGUAGE_CODE,
            self::PORTUGUESE_LANGUAGE_CODE,
            self::ROMANIAN_LANGUAGE_CODE,
            self::RUSSIAN_LANGUAGE_CODE,
            self::SERBOCROATIAN_LANGUAGE_CODE,
            self::TAGALOG_LANGUAGE_CODE,
            self::UKRAINIAN_LANGUAGE_CODE,
            self::SPANISH_LANGUAGE_CODE,
            self::LATIN_LANGUAGE_CODE,
            self::SWEDISH_LANGUAGE_CODE,
            self::ESTONIAN_LANGUAGE_CODE,
            self::ENGLISH_LANGUAGE_CODE,
            self::DUTCH_LANGUAGE_CODE,
            self::HINDI_LANGUAGE_CODE,
            self::GEORGIAN_LANGUAGE_CODE,
            self::TURKISH_LANGUAGE_CODE,
            self::ALBANIAN_LANGUAGE_CODE,
            self::CZECH_LANGUAGE_CODE,
            self::AFRIKAANS_LANGUAGE_CODE,
            self::ARMENIAN_LANGUAGE_CODE,
        ];
    }

    public static function getLanguageCodesForTransliteration(): array
    {
        return [
            self::LATVIAN_LANGUAGE_CODE,
            self::RUSSIAN_LANGUAGE_CODE,
        ];
    }

}