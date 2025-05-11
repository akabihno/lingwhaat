<?php

use App\Query\PronunciationQueryEnglishLanguage;
use App\Query\PronunciationQueryLatvianLanguage;
use App\Service\WiktionaryArticlesCategoriesEnglishService;

require 'vendor/autoload.php';

// docker exec -it php-app php utils/get_categories_articles.php

$queryLatvian = new PronunciationQueryLatvianLanguage();
$queryEnglish = new PronunciationQueryEnglishLanguage();

$categoriesService = new WiktionaryArticlesCategoriesEnglishService($queryLatvian, $queryEnglish);

$categoriesService->getArticlesByCategory();