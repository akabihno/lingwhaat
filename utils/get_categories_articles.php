<?php

use App\Query\PronunciationQueryFrenchLanguage;
use App\Query\PronunciationQueryGermanLanguage;
use App\Query\PronunciationQueryLatvianLanguage;
use App\Query\PronunciationQueryPolishLanguage;
use App\Service\WiktionaryArticlesCategoriesFrenchService;
use App\Service\WiktionaryArticlesCategoriesGermanService;
use App\Service\WiktionaryArticlesCategoriesLatvianService;
use App\Service\WiktionaryArticlesCategoriesPolishService;

require 'vendor/autoload.php';

$queryLatvian = new PronunciationQueryLatvianLanguage();
$queryGerman = new PronunciationQueryGermanLanguage();
$queryFrench = new PronunciationQueryFrenchLanguage();

$categoriesService = new WiktionaryArticlesCategoriesFrenchService($queryLatvian, $queryFrench);

$categoriesService->getArticlesByCategory();