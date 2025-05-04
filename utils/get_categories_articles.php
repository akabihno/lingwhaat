<?php

use App\Query\PronunciationQueryLatvianLanguage;
use App\Query\PronunciationQuerySpanishLanguage;
use App\Service\WiktionaryArticlesCategoriesSpanishService;

require 'vendor/autoload.php';

// docker exec -it php-app php utils/get_categories_articles.php

$queryLatvian = new PronunciationQueryLatvianLanguage();
$querySpanish = new PronunciationQuerySpanishLanguage();

$categoriesService = new WiktionaryArticlesCategoriesSpanishService($queryLatvian, $querySpanish);

$categoriesService->getArticlesByCategory();