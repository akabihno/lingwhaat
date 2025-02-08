<?php

namespace App\Service;

class WiktionaryArticlesCategoriesService
{
    const WIKTIONARY_BASE_API_LINK = 'https://en.wiktionary.org/w/api.php';
    public function getArticlesByCategory(): void
    {
        $params = [
            "cmdir" => "desc",
            "format" => "json",
            "list" => "categorymembers",
            "action" => "query",
            "cmtitle" => "Category:Latvian_lemmas",
            "cmsort" => "timestamp"
        ];

        $url = self::WIKTIONARY_BASE_API_LINK . "?" . http_build_query( $params );

        $ch = curl_init( $url );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        $output = curl_exec( $ch );
        curl_close( $ch );

        $result = json_decode( $output, true );
        var_dump($result);

        foreach($result["query"]["categorymembers"] as $lemmaArray) {
            foreach ($lemmaArray as $lemma) {
                echo( $lemma["title"] . "\n" );
            }
        }
    }

}