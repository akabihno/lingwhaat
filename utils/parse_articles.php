<?php

require 'vendor/autoload.php';

use App\Query\PronunciationQueryLatvianLanguage;
use App\Query\PronunciationQueryPolishLanguage;
use App\Query\PronunciationQueryRussianLanguage;
use App\Service\WiktionaryArticlesIpaParserService;

// $query = new PronunciationQueryRussianLanguage();
//$query = new PronunciationQueryLatvianLanguage();
$query = new PronunciationQueryPolishLanguage();

$wiktionaryArticlesService = new WiktionaryArticlesIpaParserService($query);

$wiktionaryArticlesService->run();