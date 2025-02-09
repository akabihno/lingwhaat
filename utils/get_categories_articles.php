<?php

use App\Query\PronunciationQueryGermanLanguage;
use App\Query\PronunciationQueryLatvianLanguage;
use App\Query\PronunciationQueryPolishLanguage;
use App\Service\WiktionaryArticlesCategoriesGermanService;
use App\Service\WiktionaryArticlesCategoriesLatvianService;
use App\Service\WiktionaryArticlesCategoriesPolishService;

require 'vendor/autoload.php';

$queryLatvian = new PronunciationQueryLatvianLanguage();
$queryGerman = new PronunciationQueryGermanLanguage();

$categoriesService = new WiktionaryArticlesCategoriesGermanService($queryLatvian, $queryGerman);

$categoriesService->getArticlesByCategory();