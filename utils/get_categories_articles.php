<?php

use App\Query\PronunciationQueryLatvianLanguage;
use App\Query\PronunciationQueryLithuanianLanguage;
use App\Service\WiktionaryArticlesCategoriesLithuanianService;

require 'vendor/autoload.php';

// docker exec -it php-app php utils/get_categories_articles.php

$queryLatvian = new PronunciationQueryLatvianLanguage();
$queryLithuanian = new PronunciationQueryLithuanianLanguage();

$categoriesService = new WiktionaryArticlesCategoriesLithuanianService($queryLatvian, $queryLithuanian);

$categoriesService->getArticlesByCategory();