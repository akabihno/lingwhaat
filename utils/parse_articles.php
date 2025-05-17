<?php

require 'vendor/autoload.php';

// watch --interval 5 docker exec -it php-app php utils/parse_articles.php dutch
// watch --interval 5 docker exec -it php-app php utils/parse_articles.php hindi

use App\Query\PronunciationQueryDutchLanguage;
use App\Query\PronunciationQueryHindiLanguage;
use App\Service\WiktionaryArticlesIpaParserService;


if ($argv[1] == 'dutch') {
    $query = new PronunciationQueryDutchLanguage();
} else if ($argv[1] == 'hindi') {
    $query = new PronunciationQueryHindiLanguage();
} else {
    echo "Invalid language\n";
}

if (isset($query)) {
    $wiktionaryArticlesService = new WiktionaryArticlesIpaParserService($query);

    $wiktionaryArticlesService->run();
}
