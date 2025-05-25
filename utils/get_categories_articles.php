<?php

use App\Query\PronunciationQueryGeorgianLanguage;
use App\Query\PronunciationQueryLatvianLanguage;
use App\Query\PronunciationQueryTurkishLanguage;
use App\Service\WiktionaryArticlesCategoriesGeorgianService;
use App\Service\WiktionaryArticlesCategoriesTurkishService;

require 'vendor/autoload.php';

// docker exec -it php-app php utils/get_categories_articles.php georgian
// docker exec -it php-app php utils/get_categories_articles.php hindi

$queryLatvian = new PronunciationQueryLatvianLanguage();
$queryGeorgian = new PronunciationQueryGeorgianLanguage();
$queryTurkish = new PronunciationQueryTurkishLanguage();

if ($argv[1] == 'georgian') {
    $categoriesService = new WiktionaryArticlesCategoriesGeorgianService($queryLatvian, $queryGeorgian);
} else if ($argv[1] == 'turkish') {
    $categoriesService = new WiktionaryArticlesCategoriesTurkishService($queryLatvian, $queryTurkish);
} else {
    echo "Invalid language\n";
}



if(isset($categoriesService)) {
    $categoriesService->getArticlesByCategory();
}