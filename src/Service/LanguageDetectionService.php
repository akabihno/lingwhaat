<?php

namespace App\Service;

use Symfony\Component\HttpClient\Exception\ClientException;
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
        $found = false;

        if ($languageInput) {
            foreach (explode(' ', $languageInput) as $word) {
                error_log('Test word: ' . $word);
                try {
                    $requests[$word] = [
                        'french' => $this->sendAsyncRequest('get_french_word', $word),
                        //'german' => $this->sendAsyncRequest('get_german_word', $word),
                        //'greek' => $this->sendAsyncRequest('get_greek_word', $word),
                        //'italian' => $this->sendAsyncRequest('get_italian_word', $word),
                        //'latvian' => $this->sendAsyncRequest('get_latvian_word', $word),
                        //'lithuanian' => $this->sendAsyncRequest('get_lithuanian_word', $word),
                        //'polish' => $this->sendAsyncRequest('get_polish_word', $word),
                        //'portuguese' => $this->sendAsyncRequest('get_portuguese_word', $word),
                        //'romanian' => $this->sendAsyncRequest('get_romanian_word', $word),
                        //'russian' => $this->sendAsyncRequest('get_russian_word', $word),
                        //'serbocroatian' => $this->sendAsyncRequest('get_serbocroatian_word', $word),
                        //'tagalog' => $this->sendAsyncRequest('get_tagalog_word', $word),
                        //'ukrainian' => $this->sendAsyncRequest('get_ukrainian_word', $word),
                        //'esu' => $this->sendAsyncRequest('get_esu_word', $word),
                    ];
                } catch (ClientException $e) {
                    error_log('Error creating request for word: ' . $word . ' - ' . $e->getMessage());
                    continue;
                }

            }

            foreach ($this->httpClient->stream(array_merge(...array_values($requests))) as $response => $chunk) {
                dump($requests);
                if ($chunk->isLast()) {
                    try {
                        if ($response->getStatusCode() === 200) {
                            [$word, $lang] = $this->findRequestKey($requests, $response);

                            if ($word !== null && $lang !== null) {
                                $language = constant('self::' . strtoupper($lang) . '_LANGUAGE_NAME');
                                $code = constant('self::' . strtoupper($lang) . '_LANGUAGE_CODE');
                                $found = true;
                                break;
                            }
                        }
                    } catch (ClientException $e) {
                        error_log("Request failed with status " . $response->getStatusCode() . " for URL: " . $response->getInfo('url'));
                    } catch (\Exception $e) {
                        error_log("Unexpected error for URL: " . $response->getInfo('url') . " - " . $e->getMessage());
                    }
                }
            }
        }

        return [
            'language' => $found ? $language : self::LANGUAGE_NOT_FOUND,
            'code' => $found ? $code : null
        ];
    }

    protected function sendAsyncRequest(string $route, string $word): ?ResponseInterface
    {
        $url = $this->urlGenerator->generate($route, [$route => $word], UrlGeneratorInterface::ABSOLUTE_URL);

        try {
            return $this->httpClient->request('GET', $url, [
                'timeout' => 5,
                'headers' => ['accept' => 'application/json'],
                'extra' => ['allow_redirects' => false, 'http_errors' => false]
            ]);
        } catch (\Exception $e) {
            error_log("Error fetching URL: $url - " . $e->getMessage());
            return null;
        }
    }

    protected function findRequestKey(array $requests, ResponseInterface $response): array
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