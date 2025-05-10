<?php

use App\Query\PronunciationQueryLatvianLanguage;
use App\Query\PronunciationQuerySwedishLanguage;
use App\Service\WiktionaryArticlesCategoriesSwedishService;

require 'vendor/autoload.php';

// docker exec -it php-app php utils/get_categories_articles.php

$queryLatvian = new PronunciationQueryLatvianLanguage();
$querySwedish = new PronunciationQuerySwedishLanguage();

$categoriesService = new WiktionaryArticlesCategoriesSwedishService($queryLatvian, $querySwedish);

$categoriesService->getArticlesByCategory();