<?php

namespace App\Service;

use App\Query\AbstractQuery;
use App\Query\PronunciationQueryRussianLanguage;
use Dotenv\Dotenv;
use IvoPetkov\HTML5DOMDocument;

class WiktionaryArticlesIpaParserService
{
    const string WIKTIONARY_BASE_API_LINK = 'https://en.wiktionary.org/api/rest_v1/page/html/';
    const string WIKTIONARY_BASE_URL = 'https://en.wiktionary.org/wiki/';
    const string IPA_NOT_AVAILABLE = 'Not available';

    public function __construct(protected AbstractQuery $abstractQuery,)
    {
    }
    public function run(string $language, $limit = null): void
    {
        Dotenv::createImmutable('/var/www/html/')->load();

        $uaEmail = $_ENV['WIKTIONARY_UA_EMAIL'];

        $articles = $this->getArticleNamesFromDb($language, $limit);

        if (!empty($articles)) {
            foreach ($articles as $article) {
                echo "Processing: ".$article."\n";
                $html = $this->getPageForTitle($uaEmail, $article);
                $this->processWiktionaryResult($language, $html, $article);
            }
        }

        echo "No more records to process\n";
    }

    protected function getArticleNamesFromDb(string $language, $limit = null): array
    {
        $result = [];
        $articleNamesArray = $this->abstractQuery->getArticleNames($language, $limit);

        if (empty($articleNamesArray)) {
            return $result;
        }

        foreach ($articleNamesArray as $articleNameArray) {
            $result[] = $articleNameArray['name'];
        }

        return $result;
    }

    protected function getPageForTitle(string $uaEmail, string $title): string
    {
        return $this->wiktionaryGetRequest($uaEmail, $title);
    }

    protected function processWiktionaryResult(string $language, string $html, string $article): void
    {
        $ipa = $this->parseWiktionaryResult($html);

        $this->abstractQuery->updateIpa(
            $language,
            $ipa ?: self::IPA_NOT_AVAILABLE,
            $article
        );

        $this->abstractQuery->insertLinks($language, $article, $this->generateWiktionaryLink($article));
    }

    protected function generateWiktionaryLink($article): string
    {
        return self::WIKTIONARY_BASE_URL.$article;
    }

    protected function parseWiktionaryResult(string $html): string
    {
        try {
            $dom = new HTML5DOMDocument();
            $dom->loadHTML($html);

            return $dom->querySelector('.IPA')->innerHTML ?? '';
        } catch (\Exception $e) {
            var_dump('Error parsing Wiktionary result: ' . $e->getMessage());
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