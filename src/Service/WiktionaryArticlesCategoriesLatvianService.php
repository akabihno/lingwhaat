<?php

namespace App\Service;

use App\Query\PronunciationQueryLatvianLanguage;

class WiktionaryArticlesCategoriesLatvianService
{
    const WIKTIONARY_BASE_API_LINK = 'https://en.wiktionary.org/w/api.php';
    const WIKTIONARY_RESULT_LIMIT = 500;

    public function __construct(protected PronunciationQueryLatvianLanguage $queryLatvianLanguage)
    {

    }

    public function getArticlesByCategory(): void
    {
        \Dotenv\Dotenv::createImmutable('/var/www/html/')->load();

        $uaEmail = $_ENV['WIKTIONARY_UA_EMAIL'];
        $domain = $_ENV['DOMAIN'];

        $params = [
            "cmdir" => "desc",
            "format" => "json",
            "list" => "categorymembers",
            "action" => "query",
            "cmtitle" => $this->getCmtitle(),
            "cmsort" => "timestamp",
            "cmlimit" => self::WIKTIONARY_RESULT_LIMIT
        ];

        do {
            $url = self::WIKTIONARY_BASE_API_LINK . "?" . http_build_query($params);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, $uaEmail);
            curl_setopt($ch, CURLOPT_REFERER, $domain);
            $output = curl_exec($ch);
            curl_close($ch);

            $result = json_decode($output, true);

            foreach($result["query"]["categorymembers"] as $categoryMember) {
                var_dump($categoryMember["title"]);
                $this->add($categoryMember["title"]);
            }

            if (isset($result["continue"])) {
                $params = array_merge($params, $result["continue"]);
            } else {
                break;
            }
        } while (true);
    }

    protected function getCmtitle(): string
    {
        return "Category:Latvian_lemmas";
    }

    protected function add($name): void
    {
        $this->queryLatvianLanguage->add($name);
    }

}