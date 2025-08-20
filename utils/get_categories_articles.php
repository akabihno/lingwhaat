<?php

use App\Query\PronunciationQueryCzechLanguage;
use App\Query\PronunciationQueryLatvianLanguage;
use App\Service\WiktionaryArticlesCategoriesCzechService;

require 'vendor/autoload.php';

// docker exec -it php-app php utils/get_categories_articles.php

$queryLatvian = new PronunciationQueryLatvianLanguage();
$query = new PronunciationQueryCzechLanguage();

$categoriesService = new WiktionaryArticlesCategoriesCzechService($queryLatvian, $query);

$categoriesService->getArticlesByCategory();
