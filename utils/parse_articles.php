<?php

require 'vendor/autoload.php';

// watch --interval 5 docker exec -it php-app php utils/parse_articles.php 100

use App\Query\PronunciationQueryBengaliLanguage;
use App\Service\WiktionaryArticlesIpaParserService;

$query = new PronunciationQueryBengaliLanguage();

$wiktionaryArticlesService = new WiktionaryArticlesIpaParserService($query);

$limit = null;
if ($argv[1]) {
    $limit = $argv[1];
}

$wiktionaryArticlesService->run($limit);
