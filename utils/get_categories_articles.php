<?php

use App\Service\WiktionaryArticlesCategoriesService;

require 'vendor/autoload.php';

$categoriesService = new WiktionaryArticlesCategoriesService();

$categoriesService->getArticlesByCategory();