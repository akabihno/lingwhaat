<?php

require 'vendor/autoload.php';

use App\Service\WiktionaryArticlesIpaParserService;


Dotenv\Dotenv::createImmutable(__DIR__ . '/../')->load();

$wiktionaryArticlesService = new WiktionaryArticlesIpaParserService();

$wiktionaryArticlesService->run($_ENV['WIKTIONARY_UA_EMAIL']);