<?php

use App\Query\PronunciationQueryItalianLanguage;
use App\Query\PronunciationQueryLatvianLanguage;
use App\Service\WiktionaryArticlesCategoriesItalianService;

require 'vendor/autoload.php';

// docker exec -it php-app php utils/get_categories_articles.php

$queryLatvian = new PronunciationQueryLatvianLanguage();
$queryItalian = new PronunciationQueryItalianLanguage();

$categoriesService = new WiktionaryArticlesCategoriesItalianService($queryLatvian, $queryItalian);

$categoriesService->getArticlesByCategory();