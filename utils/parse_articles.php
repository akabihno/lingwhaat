<?php

require 'vendor/autoload.php';

use App\Service\WiktionaryArticlesIpaParserService;


Dotenv\Dotenv::createImmutable(__DIR__ . '/../')->load();

$wiktionaryArticlesService = new WiktionaryArticlesIpaParserService();

echo getenv('WIKTIONARY_UA_EMAIL');

$wiktionaryArticlesService->run();