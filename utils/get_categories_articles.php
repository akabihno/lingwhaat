<?php

use App\Query\PronunciationQueryDutchLanguage;
use App\Query\PronunciationQueryHindiLanguage;
use App\Query\PronunciationQueryLatvianLanguage;
use App\Service\WiktionaryArticlesCategoriesDutchService;
use App\Service\WiktionaryArticlesCategoriesHindiService;

require 'vendor/autoload.php';

// docker exec -it php-app php utils/get_categories_articles.php dutch
// docker exec -it php-app php utils/get_categories_articles.php hindi

$queryLatvian = new PronunciationQueryLatvianLanguage();
$queryDutch = new PronunciationQueryDutchLanguage();
$queryHindi = new PronunciationQueryHindiLanguage();

if ($argv[1] == 'dutch') {
    $categoriesService = new WiktionaryArticlesCategoriesDutchService($queryLatvian, $queryDutch);
} else if ($argv[1] == 'hindi') {
    $categoriesService = new WiktionaryArticlesCategoriesHindiService($queryLatvian, $queryHindi);
} else {
    echo "Invalid language\n";
}



if(isset($categoriesService)) {
    $categoriesService->getArticlesByCategory();
}