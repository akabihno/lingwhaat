<?php

use App\Query\PronunciationQueryFrenchLanguage;
use App\Query\PronunciationQueryGermanLanguage;
use App\Query\PronunciationQueryLatvianLanguage;
use App\Query\PronunciationQueryPolishLanguage;
use App\Query\PronunciationQueryPortugueseLanguage;
use App\Query\PronunciationQuerySerboCroatianLanguage;
use App\Query\PronunciationQueryTagalogLanguage;
use App\Service\WiktionaryArticlesCategoriesFrenchService;
use App\Service\WiktionaryArticlesCategoriesGermanService;
use App\Service\WiktionaryArticlesCategoriesLatvianService;
use App\Service\WiktionaryArticlesCategoriesPolishService;
use App\Service\WiktionaryArticlesCategoriesPortugueseService;
use App\Service\WiktionaryArticlesCategoriesSerboCroatianService;
use App\Service\WiktionaryArticlesCategoriesTagalogService;

require 'vendor/autoload.php';

$queryLatvian = new PronunciationQueryLatvianLanguage();
//$queryGerman = new PronunciationQueryGermanLanguage();
//$queryFrench = new PronunciationQueryFrenchLanguage();
//$queryTagalog = new PronunciationQueryTagalogLanguage();
//$queryPortuguese = new PronunciationQueryPortugueseLanguage();
$querySerboCroatian = new PronunciationQuerySerboCroatianLanguage();

$categoriesService = new WiktionaryArticlesCategoriesSerboCroatianService($queryLatvian, $querySerboCroatian);

$categoriesService->getArticlesByCategory();