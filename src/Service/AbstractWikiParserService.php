<?php

namespace App\Service;

use Dotenv\Dotenv;

class AbstractWikiParserService
{
    protected function wikiGetRequest(string $title, string $languageCode, string $service): string
    {
        Dotenv::createImmutable('/var/www/html/')->load();

        $uaEmail = $_ENV['WIKTIONARY_UA_EMAIL'];

        $params = [
            'action' => 'parse',
            'page' => $title,
            'format' => 'json',
            'prop' => 'text'
        ];

        $url = $this->getWikiBaseApiLink($languageCode, $service) . '?' . http_build_query($params);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $uaEmail);
        $response = curl_exec($ch);

        $result = json_decode($response, true);

        return $result['parse']['text']['*'] ?? '';
    }

    protected function wikiGetRandomTitle(string $languageCode, string $service): ?string
    {
        Dotenv::createImmutable('/var/www/html/')->load();
        $uaEmail = $_ENV['WIKTIONARY_UA_EMAIL'];

        $params = [
            'action' => 'query',
            'list' => 'random',
            'rnnamespace' => 0,
            'rnlimit' => 1,
            'format' => 'json'
        ];

        $url = $this->getWikiBaseApiLink($languageCode, $service) . '?' . http_build_query($params);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $uaEmail);
        $response = curl_exec($ch);
        $result = json_decode($response, true);

        return $result['query']['random'][0]['title'] ?? null;
    }

    protected function getWikiBaseApiLink(string $language, string $service): string
    {
        return sprintf('https://%s.%s.org/w/api.php', $language, $service);
    }

}