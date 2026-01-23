<?php

namespace App\Service;

class WikipediaPatternParserService extends AbstractWikiParserService
{
    public function run(string $languageCode, int $limit): array
    {
        $randomTitle = $this->wikiGetRandomTitle($languageCode, 'wikipedia');

        if ($randomTitle) {
            echo "Fetching article: $randomTitle\n";
            $content = $this->wikiGetRequest($randomTitle, $languageCode, 'wikipedia');
            var_dump($content);
        } else {
            echo "Could not fetch a random title.\n";
        }

        return [];
    }


}