<?php

namespace App\Service;

use App\Query\PronunciationQueryRussianLanguage;

class WiktionaryArticlesIpaParserService
{
    const WIKTIONARY_BASE_API_LINK = 'https://en.wiktionary.org/api/rest_v1/page/html/';
    const WIKTIONARY_BASE_URL = 'https://en.wiktionary.org/wiki/';

    public function __construct(
        protected PronunciationQueryRussianLanguage $query
    )
    {
    }
    public function run(): void
    {
        \Dotenv\Dotenv::createImmutable('/var/www/html/')->load();

        $uaEmail = $_ENV['WIKTIONARY_UA_EMAIL'];

        $articles = $this->getArticleNamesFromDb();

        foreach ($articles as $article) {
            echo "Processing: ".$article."\n";
            $html = $this->getPageForTitle($uaEmail, $article);
            $this->processWiktionaryResult($html, $article);
        }

    }

    protected function getArticleNamesFromDb(): array
    {
        $result = [];
        $articleNamesArray = $this->query->getArticleNames();

        foreach ($articleNamesArray as $articleNameArray) {
            $result[] = $articleNameArray['name'];
        }

        return $result;
    }

    protected function getPageForTitle(string $uaEmail, string $title): string
    {
        return $this->wiktionaryGetRequest($uaEmail, $title);
    }

    protected function processWiktionaryResult(string $html, string $article): void
    {
        $ipa = $this->parseWiktionaryResult($html);

        if ($ipa) {
            $this->query->update($ipa, $article);
            $this->query->insert($article, $this->generateWiktionaryLink($article));
        }
    }

    protected function generateWiktionaryLink($article): string
    {
        return self::WIKTIONARY_BASE_URL.$article;
    }

    protected function parseWiktionaryResult(string $html): string
    {
        $dom = new \IvoPetkov\HTML5DOMDocument();
        $dom->loadHTML($html);

        $result = $dom->querySelector('.IPA')->innerHTML;

        if ($result) {
            return $result;
        } else {
            return '';
        }
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