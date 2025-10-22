<?php

require 'vendor/autoload.php';

// watch --interval 5 docker exec -it php-app php utils/parse_articles.php 100

use App\Query\PronunciationQueryAfarLanguage;
use App\Service\WiktionaryArticlesIpaParserService;

$query = new PronunciationQueryAfarLanguage();

$wiktionaryArticlesService = new WiktionaryArticlesIpaParserService($query);

$limit = null;
if ($argv[1]) {
    $limit = $argv[1];
}

$wiktionaryArticlesService->run($limit);
