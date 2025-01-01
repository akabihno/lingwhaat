<?php

require 'vendor/autoload.php';

use App\Service\WiktionaryArticlesIpaParserService;
use Dotenv\Dotenv;


Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/../')->load();

$wiktionaryArticlesService = new WiktionaryArticlesIpaParserService();

echo getenv('WIKTIONARY_UA_EMAIL');

$wiktionaryArticlesService->run();