<?php

use App\Query\PronunciationQueryEstonianLanguage;
use App\Query\PronunciationQueryLatvianLanguage;
use App\Service\WiktionaryArticlesCategoriesEstonianService;

require 'vendor/autoload.php';

// docker exec -it php-app php utils/get_categories_articles.php

$queryLatvian = new PronunciationQueryLatvianLanguage();
$queryEstonian = new PronunciationQueryEstonianLanguage();

$categoriesService = new WiktionaryArticlesCategoriesEstonianService($queryLatvian, $queryEstonian);

$categoriesService->getArticlesByCategory();