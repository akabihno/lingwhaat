<?php

use App\Query\PronunciationQueryLatinLanguage;
use App\Query\PronunciationQueryLatvianLanguage;
use App\Service\WiktionaryArticlesCategoriesLatinService;

require 'vendor/autoload.php';

// docker exec -it php-app php utils/get_categories_articles.php

$queryLatvian = new PronunciationQueryLatvianLanguage();
$queryLatin = new PronunciationQueryLatinLanguage();

$categoriesService = new WiktionaryArticlesCategoriesLatinService($queryLatvian, $queryLatin);

$categoriesService->getArticlesByCategory();