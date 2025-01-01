<?php

namespace App\Service;

class WiktionaryArticlesIpaParserService
{
    const WIKTIONARY_BASE_API_LINK = 'https://en.wiktionary.org/api/rest_v1/page/html/';
    public function run(string $uaEmail): void
    {
        $this->getPageForTitle($uaEmail, 'apple');

    }

    protected function getPageForTitle(string $uaEmail, string $title): void
    {
        $this->wiktionaryGetRequest($uaEmail, $title);

    }

    protected function wiktionaryGetRequest(string $uaEmail, string $title): void
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, self::WIKTIONARY_BASE_API_LINK . $title);
        curl_setopt($ch, CURLOPT_USERAGENT, $uaEmail);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);

        curl_close($ch);

        var_dump($response);

    }

}