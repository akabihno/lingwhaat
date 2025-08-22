<?php

use App\Query\PronunciationQueryAfrikaansLanguage;
use App\Query\PronunciationQueryLatvianLanguage;
use App\Service\WiktionaryArticlesCategoriesAfrikaansService;

require 'vendor/autoload.php';

// docker exec -it php-app php utils/get_categories_articles.php

$queryLatvian = new PronunciationQueryLatvianLanguage();
$query = new PronunciationQueryAfrikaansLanguage();

$categoriesService = new WiktionaryArticlesCategoriesAfrikaansService($queryLatvian, $query);

$categoriesService->getArticlesByCategory();
