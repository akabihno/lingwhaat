<?php

namespace App\Service;

use App\Query\AbstractQuery;
use App\Service\Logging\ElasticsearchLogger;
use DOMDocument;
use DOMXPath;
use Dotenv\Dotenv;

class WiktionaryArticlesIpaParserService
{
    const string WIKTIONARY_BASE_API_LINK = 'https://en.wiktionary.org/w/api.php';
    const string WIKTIONARY_BASE_URL = 'https://en.wiktionary.org/wiki/';
    const string IPA_NOT_AVAILABLE = 'Not available';

    public function __construct(
        protected AbstractQuery $abstractQuery,
        protected ElasticsearchLogger $logger,
    )
    {
    }
    public function run(string $language, int $limit = 0): void
    {
        Dotenv::createImmutable('/var/www/html/')->load();

        $uaEmail = $_ENV['WIKTIONARY_UA_EMAIL'];

        $articles = $this->getArticleNamesFromDb($language, $limit);

        if (!empty($articles)) {
            $ipaUpdates = [];
            $linksInserts = [];

            foreach ($articles as $article) {
                echo "Processing: ".$article."\n";
                $html = $this->getPageForTitle($uaEmail, $article, $language);
                $data = $this->processWiktionaryResult($language, $html, $article);

                if ($data) {
                    $ipaUpdates[] = $data['ipa'];
                    $linksInserts[] = $data['link'];
                }
            }

            if (!empty($ipaUpdates)) {
                $this->abstractQuery->bulkUpdateIpa($language, $ipaUpdates);
            }

            if (!empty($linksInserts)) {
                $this->abstractQuery->bulkInsertLinks($language, $linksInserts);
            }
        }

        echo "No more records to process\n";
    }

    protected function getArticleNamesFromDb(string $language, int $limit = 0): array
    {
        $result = [];
        $articleNamesArray = $this->abstractQuery->getArticleNames($language, $limit);

        if (empty($articleNamesArray)) {
            return $result;
        }

        foreach ($articleNamesArray as $articleNameArray) {
            $result[] = $articleNameArray['name'];
        }

        $this->logger->info(
            'Got '.count($result).' articles from DB.',
            ['service' => '[WiktionaryArticlesIpaParserService]']
        );

        return $result;
    }

    protected function getPageForTitle(string $uaEmail, string $title, string $language): string
    {
        return $this->wiktionaryGetRequest($uaEmail, $title, $language);
    }

    protected function processWiktionaryResult(string $language, string $html, string $article): ?array
    {
        $ipa = $this->parseWiktionaryResult($html, $language);

        return [
            'ipa' => [
                'name' => $article,
                'ipa' => $ipa ?: self::IPA_NOT_AVAILABLE
            ],
            'link' => [
                'name' => $article,
                'link' => $this->generateWiktionaryLink($article, $language)
            ]
        ];
    }

    protected function generateWiktionaryLink($article, $language): string
    {
        return $this->getWiktionaryBaseUrl($language).$article;
    }

    protected function getWiktionaryBaseUrl($language): string
    {
        if ($language == 'dutch') {
            return "https://nl.wiktionary.org/wiki/";
        }
        return self::WIKTIONARY_BASE_URL;
    }

    protected function parseWiktionaryResult(string $html, string $language): string
    {
        try {
            $dom = new DOMDocument();
            $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
            if (!$html) {
                return '';
            }
            @$dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

            $xpath = new DOMXPath($dom);
            $languageName = ucfirst(strtolower($language));
            $headings = $xpath->query("//h2[.//span[contains(text(), '$languageName')]]");

            if ($headings->length > 0) {
                $ipaNodes = $xpath->query(
                    "//h2[.//span[contains(text(), '$languageName')]]/following::*[contains(concat(' ', normalize-space(@class), ' '), ' IPA ')]"
                );

                if ($ipaNodes->length > 0) {
                    $allHeadings = $xpath->query("//h2");
                    $nextHeadingIndex = -1;

                    for ($i = 0; $i < $allHeadings->length; $i++) {
                        $headingText = $allHeadings->item($i)->textContent;
                        if (strpos($headingText, $languageName) !== false) {
                            if ($i + 1 < $allHeadings->length) {
                                $nextHeadingIndex = $i + 1;
                            }
                            break;
                        }
                    }

                    foreach ($ipaNodes as $ipaNode) {
                        $isInCorrectSection = true;

                        if ($nextHeadingIndex >= 0) {
                            $nextHeading = $allHeadings->item($nextHeadingIndex);
                            $compareResult = $ipaNode->compareDocumentPosition($nextHeading);

                            if (!($compareResult & 4)) {
                                $isInCorrectSection = false;
                            }
                        }

                        if ($isInCorrectSection) {
                            return trim($ipaNode->textContent);
                        }
                    }
                }
            }

            // Fallback: if language-specific search fails, try to get the first IPA
            $nodes = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' IPA ')]");
            if ($nodes->length > 0) {
                return trim($nodes->item(0)->textContent);
            }

            return '';
        } catch (\Exception $e) {
            var_dump('Error parsing Wiktionary result: ' . $e->getMessage());
            return '';
        }
    }

    protected function wiktionaryGetRequest(string $uaEmail, string $title, string $language): string
    {
        $params = [
            'action' => 'parse',
            'page' => $title,
            'format' => 'json',
            'prop' => 'text'
        ];

        $url = $this->getWiktionaryBaseApiLink($language) . '?' . http_build_query($params);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $uaEmail);
        $response = curl_exec($ch);

        $result = json_decode($response, true);

        return $result['parse']['text']['*'] ?? '';
    }

    protected function getWiktionaryBaseApiLink(string $language): string
    {
        if ($language == 'dutch') {
            return "https://nl.wiktionary.org/w/api.php";
        }
        return self::WIKTIONARY_BASE_API_LINK;
    }

}