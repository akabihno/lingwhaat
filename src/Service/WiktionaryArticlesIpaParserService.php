<?php

namespace App\Service;

use App\Query\PronunciationQueryRussianLanguage;

class WiktionaryArticlesIpaParserService
{
    const WIKTIONARY_BASE_API_LINK = 'https://en.wiktionary.org/api/rest_v1/page/html/';

    public function __construct(
        protected PronunciationQueryRussianLanguage $query
    )
    {
    }
    public function run(string $uaEmail): void
    {
        \Dotenv\Dotenv::createImmutable(__DIR__ . '/../')->load();

        $article = $this->getArticleNameFromDb();

        $html = $this->getPageForTitle($uaEmail, $article);

        $this->parseWiktionaryResult($html);

    }

    protected function getArticleNameFromDb(): string
    {
        $this->query->getArticleName();

        return 'apple';
    }

    protected function getPageForTitle(string $uaEmail, string $title): string
    {
        return $this->wiktionaryGetRequest($uaEmail, $title);
    }

    protected function parseWiktionaryResult(string $html)
    {
        $dom = new \IvoPetkov\HTML5DOMDocument();
        $dom->loadHTML($html);

        echo $dom->querySelector('.IPA')->innerHTML;
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