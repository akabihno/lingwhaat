<?php

use App\Query\PronunciationQueryAlbanianLanguage;
use App\Query\PronunciationQueryLatvianLanguage;
use App\Service\WiktionaryArticlesCategoriesAlbanianService;

require 'vendor/autoload.php';

// docker exec -it php-app php utils/get_categories_articles.php

$queryLatvian = new PronunciationQueryLatvianLanguage();
$queryAlbanian = new PronunciationQueryAlbanianLanguage();

$categoriesService = new WiktionaryArticlesCategoriesAlbanianService($queryLatvian, $queryAlbanian);

$categoriesService->getArticlesByCategory();
