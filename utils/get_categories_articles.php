<?php

use App\Query\PronunciationQueryBengaliLanguage;
use App\Query\PronunciationQueryLatvianLanguage;
use App\Service\WiktionaryArticlesCategoriesBengaliService;

require 'vendor/autoload.php';

// docker exec -it php-app php utils/get_categories_articles.php

$queryLatvian = new PronunciationQueryLatvianLanguage();
$query = new PronunciationQueryBengaliLanguage();

$categoriesService = new WiktionaryArticlesCategoriesBengaliService($queryLatvian, $query);

$categoriesService->getArticlesByCategory();
