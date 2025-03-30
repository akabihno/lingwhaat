<?php

use App\Query\PronunciationQueryItalianLanguage;
use App\Query\PronunciationQueryLatvianLanguage;
use App\Service\WiktionaryArticlesCategoriesItalianService;

require 'vendor/autoload.php';

$queryLatvian = new PronunciationQueryLatvianLanguage();
$queryItalian = new PronunciationQueryItalianLanguage();

$categoriesService = new WiktionaryArticlesCategoriesItalianService($queryLatvian, $queryItalian);

$categoriesService->getArticlesByCategory();