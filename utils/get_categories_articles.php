<?php

use App\Query\PronunciationQueryGreekLanguage;
use App\Query\PronunciationQueryLatvianLanguage;
use App\Service\WiktionaryArticlesCategoriesGreekService;

require 'vendor/autoload.php';

// docker exec -it php-app php utils/get_categories_articles.php

$queryLatvian = new PronunciationQueryLatvianLanguage();
$queryGreek = new PronunciationQueryGreekLanguage();

$categoriesService = new WiktionaryArticlesCategoriesGreekService($queryLatvian, $queryGreek);

$categoriesService->getArticlesByCategory();