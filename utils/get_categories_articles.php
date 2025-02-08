<?php

use App\Query\PronunciationQueryLatvianLanguage;
use App\Query\PronunciationQueryPolishLanguage;
use App\Service\WiktionaryArticlesCategoriesLatvianService;
use App\Service\WiktionaryArticlesCategoriesPolishService;

require 'vendor/autoload.php';

$queryLatvian = new PronunciationQueryLatvianLanguage();
//$categoriesService = new WiktionaryArticlesCategoriesLatvianService($queryLatvian);

$queryPolish = new PronunciationQueryPolishLanguage();
$categoriesService = new WiktionaryArticlesCategoriesPolishService($queryLatvian, $queryPolish);

$categoriesService->getArticlesByCategory();