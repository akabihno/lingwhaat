<?php

require 'vendor/autoload.php';

use App\Service\WiktionaryArticlesIpaParserService;


Dotenv\Dotenv::createImmutable(__DIR__ . '/../')->load();

$wiktionaryArticlesService = new WiktionaryArticlesIpaParserService();

$article = $argv[1];

if ($article) {
    $wiktionaryArticlesService->run($_ENV['WIKTIONARY_UA_EMAIL'], $article);
}