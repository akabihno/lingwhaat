<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

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
    public function __construct(protected HttpClientInterface $httpClient, protected UrlGeneratorInterface $urlGenerator)
    {
    }

    public function process($languageInput): array
    {
        $language = self::LANGUAGE_NOT_FOUND;
        $code = null;
        $requests = [];

        if ($languageInput) {
            foreach (explode(' ', $languageInput) as $word) {
                $requests[$word]['french'] = $this->sendAsyncRequest('get_french_word', $word);
                $requests[$word]['german'] = $this->sendAsyncRequest('get_german_word', $word);
                $requests[$word]['greek'] = $this->sendAsyncRequest('get_greek_word', $word);
                $requests[$word]['italian'] = $this->sendAsyncRequest('get_italian_word', $word);
                $requests[$word]['latvian'] = $this->sendAsyncRequest('get_latvian_word', $word);
                $requests[$word]['lithuanian'] = $this->sendAsyncRequest('get_lithuanian_word', $word);
                $requests[$word]['polish'] = $this->sendAsyncRequest('get_polish_word', $word);
                $requests[$word]['portuguese'] = $this->sendAsyncRequest('get_portuguese_word', $word);
                $requests[$word]['romanian'] = $this->sendAsyncRequest('get_romanian_word', $word);
                $requests[$word]['russian'] = $this->sendAsyncRequest('get_russian_word', $word);
                $requests[$word]['serbocroatian'] = $this->sendAsyncRequest('get_serbocroatian_word', $word);
                $requests[$word]['tagalog'] = $this->sendAsyncRequest('get_tagalog_word', $word);
                $requests[$word]['ukrainian'] = $this->sendAsyncRequest('get_ukrainian_word', $word);
                $requests[$word]['esu'] = $this->sendAsyncRequest('get_esu_word', $word);

            }

            foreach ($this->httpClient->stream(array_merge(...array_values($requests))) as $response => $chunk) {
                if ($chunk->isLast()) {
                    $statusCode = $response->getStatusCode();
                    if ($statusCode >= 500) {
                        break;
                    }
                    [$word, $lang] = $this->findRequestKey($requests, $response);

                    if ($lang === 'french') {
                        $language = self::FRENCH_LANGUAGE_NAME;
                        $code = self::FRENCH_LANGUAGE_CODE;
                    } elseif ($lang === 'german') {
                        $language = self::GERMAN_LANGUAGE_NAME;
                        $code = self::GERMAN_LANGUAGE_CODE;
                    } elseif ($lang === 'greek') {
                        $language = self::GREEK_LANGUAGE_NAME;
                        $code = self::GREEK_LANGUAGE_CODE;
                    } elseif ($lang === 'italian') {
                        $language = self::ITALIAN_LANGUAGE_NAME;
                        $code = self::ITALIAN_LANGUAGE_CODE;
                    } elseif ($lang === 'latvian') {
                        $language = self::LATVIAN_LANGUAGE_NAME;
                        $code = self::LATVIAN_LANGUAGE_CODE;
                    } elseif ($lang === 'lithuanian') {
                        $language = self::LITHUANIAN_LANGUAGE_NAME;
                        $code = self::LITHUANIAN_LANGUAGE_CODE;
                    } elseif ($lang === 'polish') {
                        $language = self::POLISH_LANGUAGE_NAME;
                        $code = self::POLISH_LANGUAGE_CODE;
                    } elseif ($lang === 'portuguese') {
                        $language = self::PORTUGUESE_LANGUAGE_NAME;
                        $code = self::PORTUGUESE_LANGUAGE_CODE;
                    } elseif ($lang === 'romanian') {
                        $language = self::ROMANIAN_LANGUAGE_NAME;
                        $code = self::ROMANIAN_LANGUAGE_CODE;
                    } elseif ($lang === 'russian') {
                        $language = self::RUSSIAN_LANGUAGE_NAME;
                        $code = self::RUSSIAN_LANGUAGE_CODE;
                    } elseif ($lang === 'serbocroatian') {
                        $language = self::SERBOCROATIAN_LANGUAGE_NAME;
                        $code = self::SERBOCROATIAN_LANGUAGE_CODE;
                    } elseif ($lang === 'tagalog') {
                        $language = self::TAGALOG_LANGUAGE_NAME;
                        $code = self::TAGALOG_LANGUAGE_CODE;
                    } elseif ($lang === 'ukrainian') {
                        $language = self::UKRAINIAN_LANGUAGE_NAME;
                        $code = self::UKRAINIAN_LANGUAGE_CODE;
                    } elseif ($lang === 'esu') {
                        $language = self::ESU_LANGUAGE_NAME;
                        $code = self::ESU_LANGUAGE_CODE;
                    }
                }
            }

        }

        return ['language' => $language, 'code' => $code];
    }

    protected function sendAsyncRequest(string $route, string $word): ResponseInterface
    {
        $url = $this->urlGenerator->generate($route, [$route => $word], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->httpClient->request('GET', $url, ['timeout' => 5]);
    }

    private function findRequestKey(array $requests, ResponseInterface $response): array
    {
        foreach ($requests as $word => $languages) {
            foreach ($languages as $lang => $req) {
                if ($req === $response) {
                    return [$word, $lang];
                }
            }
        }
        return [null, null];
    }



}