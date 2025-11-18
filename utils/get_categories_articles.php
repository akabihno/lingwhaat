<?php

use App\Query\AbstractQuery;
use App\Service\WiktionaryArticlesCategoriesService;

require 'vendor/autoload.php';

// docker exec -it php-app php utils/get_categories_articles.php bengali

$language = $argv[1];

$abstractQuery = new AbstractQuery();
$categoriesService = new WiktionaryArticlesCategoriesService($abstractQuery);

$categoriesService->getArticlesByCategory(strtolower($language));
