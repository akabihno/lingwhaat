<?php

require 'vendor/autoload.php';

// watch --interval 5 docker exec -it php-app php utils/parse_articles.php

use App\Query\PronunciationQueryAlbanianLanguage;
use App\Service\WiktionaryArticlesIpaParserService;

$query = new PronunciationQueryAlbanianLanguage();

$wiktionaryArticlesService = new WiktionaryArticlesIpaParserService($query);

$wiktionaryArticlesService->run();
