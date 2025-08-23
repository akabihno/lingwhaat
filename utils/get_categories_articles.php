<?php

use App\Query\PronunciationQueryArmenianLanguage;
use App\Query\PronunciationQueryLatvianLanguage;
use App\Service\WiktionaryArticlesCategoriesArmenianService;

require 'vendor/autoload.php';

// docker exec -it php-app php utils/get_categories_articles.php

$queryLatvian = new PronunciationQueryLatvianLanguage();
$query = new PronunciationQueryArmenianLanguage();

$categoriesService = new WiktionaryArticlesCategoriesArmenianService($queryLatvian, $query);

$categoriesService->getArticlesByCategory();
