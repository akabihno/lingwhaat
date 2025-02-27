<?php

require 'vendor/autoload.php';

use App\Query\PronunciationQueryRomanianLanguage;
use App\Service\WiktionaryArticlesIpaParserService;

$query = new PronunciationQueryRomanianLanguage();

$wiktionaryArticlesService = new WiktionaryArticlesIpaParserService($query);

$wiktionaryArticlesService->run();