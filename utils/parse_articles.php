<?php

require 'vendor/autoload.php';

// watch --interval 5 docker exec -it php-app php utils/parse_articles.php bengali 100

use App\Service\WiktionaryArticlesIpaParserService;

$language = ucfirst($argv[1]);
$limit = $argv[2] ?? 100;

$queryClassName = "\\App\\Query\\PronunciationQuery{$language}Language";
if (!class_exists($queryClassName)) {
    die("Error: Query class {$queryClassName} does not exist\n");
}
$query = new $queryClassName();

$wiktionaryArticlesService = new WiktionaryArticlesIpaParserService($query);



$wiktionaryArticlesService->run($limit);
