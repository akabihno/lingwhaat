<?php

require 'vendor/autoload.php';

// watch --interval 5 docker exec -it php-app php utils/parse_articles.php

use App\Query\PronunciationQueryLithuanianLanguage;
use App\Service\WiktionaryArticlesIpaParserService;

$query = new PronunciationQueryLithuanianLanguage();

$wiktionaryArticlesService = new WiktionaryArticlesIpaParserService($query);

$wiktionaryArticlesService->run();