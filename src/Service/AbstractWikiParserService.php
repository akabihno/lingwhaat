<?php

namespace App\Service;

use Dotenv\Dotenv;

class AbstractWikiParserService
{
    /** Hard cap the MediaWiki API puts on list=random's rnlimit. */
    private const int RANDOM_TITLE_API_LIMIT = 500;
    private const int CONNECT_TIMEOUT_SECONDS = 5;
    private const int REQUEST_TIMEOUT_SECONDS = 30;

    /** Resolved once per process — the .env file was previously re-read on every single API call. */
    private static ?string $userAgent = null;

    protected function wikiGetRequest(string $title, string $languageCode, string $service): string
    {
        $response = $this->apiGet($languageCode, $service, [
            'action' => 'parse',
            'page' => $title,
            'format' => 'json',
            'prop' => 'text'
        ]);

        return $response['parse']['text']['*'] ?? '';
    }

    /**
     * Fetch up to $count random mainspace titles in a single API call. Asking for them one at a
     * time doubles the requests we make to Wikipedia for no benefit — list=random returns distinct
     * pages within one response.
     *
     * @return string[]
     */
    protected function wikiGetRandomTitles(string $languageCode, string $service, int $count): array
    {
        $response = $this->apiGet($languageCode, $service, [
            'action' => 'query',
            'list' => 'random',
            'rnnamespace' => 0,
            'rnlimit' => max(1, min($count, self::RANDOM_TITLE_API_LIMIT)),
            'format' => 'json'
        ]);

        $titles = [];
        foreach ($response['query']['random'] ?? [] as $page) {
            if (isset($page['title']) && is_string($page['title'])) {
                $titles[] = $page['title'];
            }
        }

        return $titles;
    }

    protected function getWikiBaseApiLink(string $language, string $service): string
    {
        return sprintf('https://%s.%s.org/w/api.php', $language, $service);
    }

    /**
     * @param array<string, scalar> $params
     *
     * @return array<mixed> empty on any transport error, non-200 status or unparseable body — the
     *                      callers all treat a missing result as "skip this item and move on"
     */
    private function apiGet(string $languageCode, string $service, array $params): array
    {
        $url = $this->getWikiBaseApiLink($languageCode, $service) . '?' . http_build_query($params);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => $this->userAgent(),
            // Without timeouts a hung connection blocks the worker indefinitely; the whole message
            // is retried on failure, so giving up early is always the better trade.
            CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT_SECONDS,
            CURLOPT_TIMEOUT => self::REQUEST_TIMEOUT_SECONDS,
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($response === false || $error !== '') {
            echo sprintf("Wiki API request failed (%s): %s\n", $url, $error);
            return [];
        }

        if ($status !== 200) {
            echo sprintf("Wiki API returned HTTP %d (%s)\n", $status, $url);
            return [];
        }

        $decoded = json_decode((string) $response, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function userAgent(): string
    {
        if (self::$userAgent === null) {
            Dotenv::createImmutable('/var/www/html/')->load();
            self::$userAgent = $_ENV['WIKTIONARY_UA_EMAIL'] ?? '';
        }

        return self::$userAgent;
    }
}
