<?php

require 'vendor/autoload.php';

use App\Query\PronunciationQueryItalianLanguage;
use App\Service\WiktionaryArticlesIpaParserService;

$query = new PronunciationQueryItalianLanguage();

$wiktionaryArticlesService = new WiktionaryArticlesIpaParserService($query);

$wiktionaryArticlesService->run();