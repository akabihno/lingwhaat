<?php

require 'vendor/autoload.php';

use App\Query\PronunciationQueryLatvianLanguage;
use App\Query\PronunciationQueryRussianLanguage;
use App\Service\WiktionaryArticlesIpaParserService;

// $query = new PronunciationQueryRussianLanguage();
$query = new PronunciationQueryLatvianLanguage();

$wiktionaryArticlesService = new WiktionaryArticlesIpaParserService($query);

$wiktionaryArticlesService->run();