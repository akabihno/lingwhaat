<?php

namespace App\Service;

use App\Query\AbstractQuery;
use Dotenv\Dotenv;

class WiktionaryArticlesCategoriesService extends AbstractWiktionaryParserService
{
    const int WIKTIONARY_RESULT_LIMIT = 500;

    public function __construct(protected AbstractQuery $abstractQuery)
    {
    }

    public function getArticlesByCategory(string $language): void
    {
        Dotenv::createImmutable('/var/www/html/')->load();

        $uaEmail = $_ENV['WIKTIONARY_UA_EMAIL'];
        $domain = $_ENV['DOMAIN'];

        $params = [
            "cmdir" => "desc",
            "format" => "json",
            "list" => "categorymembers",
            "action" => "query",
            "cmtitle" => $this->getCmtitle($language),
            "cmsort" => "timestamp",
            "cmlimit" => self::WIKTIONARY_RESULT_LIMIT
        ];

        do {
            $url = $this->getWiktionaryBaseApiLink($language) . "?" . http_build_query($params);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, $uaEmail);
            curl_setopt($ch, CURLOPT_REFERER, $domain);
            $output = curl_exec($ch);

            $result = json_decode($output, true);

            foreach($result["query"]["categorymembers"] as $categoryMember) {
                echo("Processing: ".$categoryMember["title"]."\n");
                $this->abstractQuery->insertNames(strtolower($language), $categoryMember["title"]);
            }

            if (isset($result["continue"])) {
                $params = array_merge($params, $result["continue"]);
            } else {
                break;
            }
        } while (true);
    }

    protected function getCmtitle(string $language): string
    {
        if (str_contains($language, 'old')) {
            return "Category:Old_".ucfirst($this->trimLanguageName($language, 'old'))."_lemmas";
        } elseif (str_contains($language, 'middle')) {
            return "Category:Middle_".ucfirst($this->trimLanguageName($language, 'middle'))."_lemmas";
        } elseif ($language == 'dutch') {
            return "Categorie:Woorden_in_het_Nederlands";
        } elseif ($language == 'komi') {
            return "Категория:Коми-зырянский_язык";
        }
        else {
            return "Category:".ucfirst($language)."_lemmas";
        }
    }

    protected function trimLanguageName(string $language, string $prefix): string
    {
        return str_replace($prefix, '', $language);
    }

}