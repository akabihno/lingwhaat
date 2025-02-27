<?php

use App\Query\PronunciationQueryLatvianLanguage;
use App\Query\PronunciationQueryRomanianLanguage;
use App\Service\WiktionaryArticlesCategoriesRomanianService;

require 'vendor/autoload.php';

$queryLatvian = new PronunciationQueryLatvianLanguage();
$queryRomanian = new PronunciationQueryRomanianLanguage();

$categoriesService = new WiktionaryArticlesCategoriesRomanianService($queryLatvian, $queryRomanian);

$categoriesService->getArticlesByCategory();