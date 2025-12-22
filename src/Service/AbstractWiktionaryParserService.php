<?php

namespace App\Service;

abstract class AbstractWiktionaryParserService
{
    const string WIKTIONARY_BASE_API_LINK = 'https://en.wiktionary.org/w/api.php';
    const string WIKTIONARY_BASE_URL = 'https://en.wiktionary.org/wiki/';
    protected function getWiktionaryBaseApiLink(string $language): string
    {
        if ($language == 'dutch') {
            return "https://nl.wiktionary.org/w/api.php";
        } elseif ($language == 'komi') {
            return "https://ru.wiktionary.org/w/api.php";
        }
        return self::WIKTIONARY_BASE_API_LINK;
    }

    protected function getWiktionaryBaseUrl($language): string
    {
        if ($language == 'dutch') {
            return "https://nl.wiktionary.org/wiki/";
        } elseif ($language == 'komi') {
            return "https://ru.wiktionary.org/wiki/";
        }
        return self::WIKTIONARY_BASE_URL;
    }

}