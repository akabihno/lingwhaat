<?php

use App\Query\PronunciationQueryLatvianLanguage;
use App\Service\WiktionaryArticlesCategoriesService;

require 'vendor/autoload.php';

$query = new PronunciationQueryLatvianLanguage();
$categoriesService = new WiktionaryArticlesCategoriesService($query);

$categoriesService->getArticlesByCategory();