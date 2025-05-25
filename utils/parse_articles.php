<?php

require 'vendor/autoload.php';

// watch --interval 5 docker exec -it php-app php utils/parse_articles.php georgian
// watch --interval 5 docker exec -it php-app php utils/parse_articles.php turkish

use App\Query\PronunciationQueryGeorgianLanguage;
use App\Query\PronunciationQueryTurkishLanguage;
use App\Service\WiktionaryArticlesIpaParserService;


if ($argv[1] == 'georgian') {
    $query = new PronunciationQueryGeorgianLanguage();
} else if ($argv[1] == 'turkish') {
    $query = new PronunciationQueryTurkishLanguage();
} else {
    echo "Invalid language\n";
}

if (isset($query)) {
    $wiktionaryArticlesService = new WiktionaryArticlesIpaParserService($query);

    $wiktionaryArticlesService->run();
}
