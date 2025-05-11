<?php

namespace App\Service\LanguageDetection;

use App\Service\LanguageNormalizationService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

class LanguageDetectionService
{
    const FRENCH_LANGUAGE_NAME = 'French';
    const FRENCH_LANGUAGE_CODE = 'fr';
    const GERMAN_LANGUAGE_NAME = 'German';
    const GERMAN_LANGUAGE_CODE = 'de';
    const GREEK_LANGUAGE_NAME = 'Greek';
    const GREEK_LANGUAGE_CODE = 'el';
    const ITALIAN_LANGUAGE_NAME = 'Italian';
    const ITALIAN_LANGUAGE_CODE = 'it';
    const LATVIAN_LANGUAGE_NAME = 'Latvian';
    const LATVIAN_LANGUAGE_CODE = 'lv';
    const LITHUANIAN_LANGUAGE_NAME = 'Lithuanian';
    const LITHUANIAN_LANGUAGE_CODE = 'lt';
    const POLISH_LANGUAGE_NAME = 'Polish';
    const POLISH_LANGUAGE_CODE = 'pl';
    const PORTUGUESE_LANGUAGE_NAME = 'Portuguese';
    const PORTUGUESE_LANGUAGE_CODE = 'pt';
    const ROMANIAN_LANGUAGE_NAME = 'Romanian';
    const ROMANIAN_LANGUAGE_CODE = 'ro';
    const RUSSIAN_LANGUAGE_NAME = 'Russian';
    const RUSSIAN_LANGUAGE_CODE = 'ru';
    const SERBOCROATIAN_LANGUAGE_NAME = 'Serbo-Croatian';
    const SERBOCROATIAN_LANGUAGE_CODE = 'sh';
    const TAGALOG_LANGUAGE_NAME = 'Tagalog';
    const TAGALOG_LANGUAGE_CODE = 'tl';
    const UKRAINIAN_LANGUAGE_NAME = 'Ukrainian';
    const UKRAINIAN_LANGUAGE_CODE = 'uk';
    const ESU_LANGUAGE_NAME = 'Esu';
    const ESU_LANGUAGE_CODE = 'isu';
    const SPANISH_LANGUAGE_NAME = 'Spanish';
    const SPANISH_LANGUAGE_CODE = 'es';
    const LATIN_LANGUAGE_NAME = 'Latin';
    const LATIN_LANGUAGE_CODE = 'la';
    const SWEDISH_LANGUAGE_NAME = 'Swedish';
    const SWEDISH_LANGUAGE_CODE = 'sv';
    const ESTONIAN_LANGUAGE_NAME = 'Estonian';
    const ESTONIAN_LANGUAGE_CODE = 'et';
    const LANGUAGE_NOT_FOUND = 'Language not found';
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
        protected EsuLanguageService $esuLanguageService,
        protected SpanishLanguageService $spanishLanguageService,
        protected LatinLanguageService $latinLanguageService,
        protected SwedishLanguageService $swedishLanguageService,
        protected EstonianLanguageService $estonianLanguageService
    )
    {
    }

    public function process($languageInput): array
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

            $languageInput = $this->languageNormalizationService->normalizeText($languageInput);
            $words = explode(' ', $languageInput);
            $words = $this->languageNormalizationService->removeArticles($words);
            $this->logger->info(sprintf('[LanguageDetectionService][%s] Processing normalized: %s', $uuidStr, json_encode($words)));

            $count = count($words);

            foreach ($words as $word) {
                $word = $this->languageNormalizationService->normalizeWord($word);
                $this->logger->info(sprintf('[LanguageDetectionService][%s] Processing word: %s', $uuidStr, $word));

                if ($this->checkFrenchLanguage($word)) {
                    $result[$word] = ['language' => self::FRENCH_LANGUAGE_NAME, 'code' => self::FRENCH_LANGUAGE_CODE];
                    $this->logLanguageDetectionResult($uuidStr, $result[$word]);
                }
                if ($this->checkGermanLanguage($word)) {
                    $result[$word] = ['language' => self::GERMAN_LANGUAGE_NAME, 'code' => self::GERMAN_LANGUAGE_CODE];
                    $this->logLanguageDetectionResult($uuidStr, $result[$word]);
                }
                if ($this->checkGreekLanguage($word)) {
                    $result[$word] = ['language' => self::GREEK_LANGUAGE_NAME, 'code' => self::GREEK_LANGUAGE_CODE];
                    $this->logLanguageDetectionResult($uuidStr, $result[$word]);
                }
                if ($this->checkItalianLanguage($word)) {
                    $result[$word] = ['language' => self::ITALIAN_LANGUAGE_NAME, 'code' => self::ITALIAN_LANGUAGE_CODE];
                    $this->logLanguageDetectionResult($uuidStr, $result[$word]);
                }
                if ($this->checkLatvianLanguage($word)) {
                    $result[$word] = ['language' => self::LATVIAN_LANGUAGE_NAME, 'code' => self::LATVIAN_LANGUAGE_CODE];
                    $this->logLanguageDetectionResult($uuidStr, $result[$word]);
                }
                if ($this->checkLithuanianLanguage($word)) {
                    $result[$word] = ['language' => self::LITHUANIAN_LANGUAGE_NAME, 'code' => self::LITHUANIAN_LANGUAGE_CODE];
                    $this->logLanguageDetectionResult($uuidStr, $result[$word]);
                }
                if ($this->checkPolishLanguage($word)) {
                    $result[$word] = ['language' => self::POLISH_LANGUAGE_NAME, 'code' => self::POLISH_LANGUAGE_CODE];
                    $this->logLanguageDetectionResult($uuidStr, $result[$word]);
                }
                if ($this->checkPortugueseLanguage($word)) {
                    $result[$word] = ['language' => self::PORTUGUESE_LANGUAGE_NAME, 'code' => self::PORTUGUESE_LANGUAGE_CODE];
                    $this->logLanguageDetectionResult($uuidStr, $result[$word]);
                }
                if ($this->checkRomanianLanguage($word)) {
                    $result[$word] = ['language' => self::ROMANIAN_LANGUAGE_NAME, 'code' => self::ROMANIAN_LANGUAGE_CODE];
                    $this->logLanguageDetectionResult($uuidStr, $result[$word]);
                }
                if ($this->checkRussianLanguage($word)) {
                    $result[$word] = ['language' => self::RUSSIAN_LANGUAGE_NAME, 'code' => self::RUSSIAN_LANGUAGE_CODE];
                    $this->logLanguageDetectionResult($uuidStr, $result[$word]);
                }
                if ($this->checkSerboCroatianLanguage($word)) {
                    $result[$word] = ['language' => self::SERBOCROATIAN_LANGUAGE_NAME, 'code' => self::SERBOCROATIAN_LANGUAGE_CODE];
                    $this->logLanguageDetectionResult($uuidStr, $result[$word]);
                }
                if ($this->checkTagalogLanguage($word)) {
                    $result[$word] = ['language' => self::TAGALOG_LANGUAGE_NAME, 'code' => self::TAGALOG_LANGUAGE_CODE];
                    $this->logLanguageDetectionResult($uuidStr, $result[$word]);
                }
                if ($this->checkUkrainianLanguage($word)) {
                    $result[$word] = ['language' => self::UKRAINIAN_LANGUAGE_NAME, 'code' => self::UKRAINIAN_LANGUAGE_CODE];
                    $this->logLanguageDetectionResult($uuidStr, $result[$word]);
                }
                if ($this->checkEsuLanguage($word)) {
                    $result[$word] = ['language' => self::ESU_LANGUAGE_NAME, 'code' => self::ESU_LANGUAGE_CODE];
                    $this->logLanguageDetectionResult($uuidStr, $result[$word]);
                }
                if ($this->checkSpanishLanguage($word)) {
                    $result[$word] = ['language' => self::SPANISH_LANGUAGE_NAME, 'code' => self::SPANISH_LANGUAGE_CODE];
                    $this->logLanguageDetectionResult($uuidStr, $result[$word]);
                }
                if ($this->checkLatinLanguage($word)) {
                    $result[$word] = ['language' => self::LATIN_LANGUAGE_NAME, 'code' => self::LATIN_LANGUAGE_CODE];
                    $this->logLanguageDetectionResult($uuidStr, $result[$word]);
                }
                if ($this->checkSwedishLanguage($word)) {
                    $result[$word] = ['language' => self::SWEDISH_LANGUAGE_NAME, 'code' => self::SWEDISH_LANGUAGE_CODE];
                    $this->logLanguageDetectionResult($uuidStr, $result[$word]);
                }
                if ($this->checkEstonianLanguage($word)) {
                    $result[$word] = ['language' => self::ESTONIAN_LANGUAGE_NAME, 'code' => self::ESTONIAN_LANGUAGE_CODE];
                    $this->logLanguageDetectionResult($uuidStr, $result[$word]);
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

        $finish = microtime(true);

        return [
            'language' => $language,
            'code' => $code,
            'count' => $count,
            'matches' => $matchCount,
            'time' => $finish - $start
        ];
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

    protected function checkEsuLanguage(string $word): bool
    {
        return $this->esuLanguageService->checkLanguage($word);
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

}