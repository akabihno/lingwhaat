<?php

namespace App\Service;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class LanguageDetectionService
{
    const FRENCH_LANGUAGE_NAME = 'French';
    const FRENCH_LANGUAGE_CODE = 'fr';
    const GERMAN_LANGUAGE_NAME = 'German';
    const GERMAN_LANGUAGE_CODE = 'de';
    const ITALIAN_LANGUAGE_NAME = 'Italian';
    const ITALIAN_LANGUAGE_CODE = 'it';
    const LATVIAN_LANGUAGE_NAME = 'Latvian';
    const LATVIAN_LANGUAGE_CODE = 'lv';
    const POLISH_LANGUAGE_NAME = 'Polish';
    const POLISH_LANGUAGE_CODE = 'pl';
    const PORTUGUESE_LANGUAGE_NAME = 'Portuguese';
    const PORTUGUESE_LANGUAGE_CODE = 'pt';
    const ROMANIAN_LANGUAGE_NAME = 'Romanian';
    const RUSSIAN_LANGUAGE_NAME = 'Russian';
    const RUSSIAN_LANGUAGE_CODE = 'ru';
    const ROMANIAN_LANGUAGE_CODE = 'ro';
    const ESU_LANGUAGE_NAME = 'Esu';
    const ESU_LANGUAGE_CODE = 'isu';
    const LANGUAGE_NOT_FOUND = 'Language not found';
    public function __construct(protected HttpClientInterface $httpClient, protected UrlGeneratorInterface $urlGenerator)
    {
    }

    public function process($languageInput): array
    {
        $language = self::LANGUAGE_NOT_FOUND;
        $code = null;

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
                if ($this->checkItalianLanguage($word)) {
                    $language = self::ITALIAN_LANGUAGE_NAME;
                    $code = self::ITALIAN_LANGUAGE_CODE;
                }
                if ($this->checkLatvianLanguage($word)) {
                    $language = self::LATVIAN_LANGUAGE_NAME;
                    $code = self::LATVIAN_LANGUAGE_CODE;
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
                if ($this->checkEsuLanguage($word)) {
                    $language = self::ESU_LANGUAGE_NAME;
                    $code = self::ESU_LANGUAGE_CODE;
                }

            }
        }

        return ['language' => $language, 'code' => $code];
    }

    protected function checkFrenchLanguage(string $word): bool
    {
        return $this->checkLanguage('get_french_word', $word);
    }

    protected function checkGermanLanguage(string $word): bool
    {
        return $this->checkLanguage('get_german_word', $word);
    }

    protected function checkItalianLanguage(string $word): bool
    {
        return $this->checkLanguage('get_italian_word', $word);
    }

    protected function checkLatvianLanguage(string $word): bool
    {
        return $this->checkLanguage('get_latvian_word', $word);
    }

    protected function checkPolishLanguage(string $word): bool
    {
        return $this->checkLanguage('get_polish_word', $word);
    }

    protected function checkPortugueseLanguage(string $word): bool
    {
        return $this->checkLanguage('get_portuguese_word', $word);
    }

    protected function checkRomanianLanguage(string $word): bool
    {
        return $this->checkLanguage('get_romanian_word', $word);
    }

    protected function checkRussianLanguage(string $word): bool
    {
        return $this->checkLanguage('get_russian_word', $word);
    }

    protected function checkEsuLanguage(string $word): bool
    {
        return $this->checkLanguage('get_esu_word', $word);
    }

    protected function checkLanguage(string $route, string $word): bool
    {
        $url = $this->urlGenerator->generate($route, [$route => $word], UrlGeneratorInterface::ABSOLUTE_URL);

        try {
            $response = $this->httpClient->request('GET', $url);
            $responseContent = $response->getContent();

            return true;
        } catch (\Exception $exception) {
            return false;
        }
    }

}