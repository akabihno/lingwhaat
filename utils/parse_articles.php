<?php

require 'vendor/autoload.php';

use App\Query\PronunciationQueryRussianLanguage;
use App\Service\WiktionaryArticlesIpaParserService;

$query = new PronunciationQueryRussianLanguage();

$wiktionaryArticlesService = new WiktionaryArticlesIpaParserService($query);

$wiktionaryArticlesService->run();