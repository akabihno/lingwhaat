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