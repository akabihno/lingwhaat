<?php

namespace App\Service;

class WiktionaryArticlesIpaParserService
{
    const WIKTIONARY_BASE_API_LINK = 'https://en.wiktionary.org/api/rest_v1/page/html/';
    public function run(string $uaEmail, string $article): void
    {
        $html = $this->getPageForTitle($uaEmail, $article);

        $this->parseWiktionaryResult($html);

    }

    protected function getPageForTitle(string $uaEmail, string $title): string
    {
        return $this->wiktionaryGetRequest($uaEmail, $title);
    }

    protected function parseWiktionaryResult(string $html)
    {
        $dom = new IvoPetkov\HTML5DOMDocument();
        $dom->loadHTML($html);
        echo $dom->saveHTML();

        echo $dom->querySelector('h3 id="Pronunciation"')->innerHTML;
    }

    protected function wiktionaryGetRequest(string $uaEmail, string $title): string
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, self::WIKTIONARY_BASE_API_LINK . $title);
        curl_setopt($ch, CURLOPT_USERAGENT, $uaEmail);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);

        curl_close($ch);

        return $response;

    }

}