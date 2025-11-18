<?php

require 'vendor/autoload.php';

// watch --interval 5 docker exec -it php-app php utils/parse_articles.php bengali 100

use App\Query\AbstractQuery;
use App\Service\WiktionaryArticlesIpaParserService;

$language = $argv[1];
$limit = $argv[2] ?? 100;

$abstractQuery = new AbstractQuery();
$wiktionaryArticlesService = new WiktionaryArticlesIpaParserService($abstractQuery);

$wiktionaryArticlesService->run(strtolower($language), $limit);
