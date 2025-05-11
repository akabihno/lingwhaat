<?php

require 'vendor/autoload.php';

// watch --interval 5 docker exec -it php-app php utils/parse_articles.php

use App\Query\PronunciationQueryEnglishLanguage;
use App\Service\WiktionaryArticlesIpaParserService;

$query = new PronunciationQueryEnglishLanguage();

$wiktionaryArticlesService = new WiktionaryArticlesIpaParserService($query);

$wiktionaryArticlesService->run();