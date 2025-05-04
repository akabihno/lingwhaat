<?php

namespace App\Service\LanguageDetection;

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
    const LANGUAGE_NOT_FOUND = 'Language not found';
    public function __construct(
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
        protected EsuLanguageService $esuLanguageService
    )
    {
    }

    public function process($languageInput): array
    {
        $language = self::LANGUAGE_NOT_FOUND;
        $code = null;
        $start = microtime(true);

        if ($languageInput) {
            foreach (explode(' ', $languageInput) as $word) {
                if ($this->checkFrenchLanguage($word)) {
                    $language = self::FRENCH_LANGUAGE_NAME;
                    $code = self::FRENCH_LANGUAGE_CODE;
                }
                if ($this->checkGermanLanguage($word)) {
                    $language = self::GERMAN_LANGUAGE_NAME;
                    $code = self::GERMAN_LANGUAGE_CODE;
                }
                if ($this->checkGreekLanguage($word)) {
                    $language = self::GREEK_LANGUAGE_NAME;
                    $code = self::GREEK_LANGUAGE_CODE;
                }
                if ($this->checkItalianLanguage($word)) {
                    $language = self::ITALIAN_LANGUAGE_NAME;
                    $code = self::ITALIAN_LANGUAGE_CODE;
                }
                if ($this->checkLatvianLanguage($word)) {
                    $language = self::LATVIAN_LANGUAGE_NAME;
                    $code = self::LATVIAN_LANGUAGE_CODE;
                }
                if ($this->checkLithuanianLanguage($word)) {
                    $language = self::LITHUANIAN_LANGUAGE_NAME;
                    $code = self::LITHUANIAN_LANGUAGE_CODE;
                }
                if ($this->checkPolishLanguage($word)) {
                    $language = self::POLISH_LANGUAGE_NAME;
                    $code = self::POLISH_LANGUAGE_CODE;
                }
                if ($this->checkPortugueseLanguage($word)) {
                    $language = self::PORTUGUESE_LANGUAGE_NAME;
                    $code = self::PORTUGUESE_LANGUAGE_CODE;
                }
                if ($this->checkRomanianLanguage($word)) {
                    $language = self::ROMANIAN_LANGUAGE_NAME;
                    $code = self::ROMANIAN_LANGUAGE_CODE;
                }
                if ($this->checkRussianLanguage($word)) {
                    $language = self::RUSSIAN_LANGUAGE_NAME;
                    $code = self::RUSSIAN_LANGUAGE_CODE;
                }
                if ($this->checkSerboCroatianLanguage($word)) {
                    $language = self::SERBOCROATIAN_LANGUAGE_NAME;
                    $code = self::SERBOCROATIAN_LANGUAGE_CODE;
                }
                if ($this->checkTagalogLanguage($word)) {
                    $language = self::TAGALOG_LANGUAGE_NAME;
                    $code = self::TAGALOG_LANGUAGE_CODE;
                }
                if ($this->checkUkrainianLanguage($word)) {
                    $language = self::UKRAINIAN_LANGUAGE_NAME;
                    $code = self::UKRAINIAN_LANGUAGE_CODE;
                }
                if ($this->checkEsuLanguage($word)) {
                    $language = self::ESU_LANGUAGE_NAME;
                    $code = self::ESU_LANGUAGE_CODE;
                }

            }
        }

        $finish = microtime(true);

        return ['language' => $language, 'code' => $code, 'time' => $finish - $start];
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

}