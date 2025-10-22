<?php

use App\Query\PronunciationQueryAfarLanguage;
use App\Query\PronunciationQueryLatvianLanguage;
use App\Service\WiktionaryArticlesCategoriesAfarService;

require 'vendor/autoload.php';

// docker exec -it php-app php utils/get_categories_articles.php

$queryLatvian = new PronunciationQueryLatvianLanguage();
$query = new PronunciationQueryAfarLanguage();

$categoriesService = new WiktionaryArticlesCategoriesAfarService($queryLatvian, $query);

$categoriesService->getArticlesByCategory();
