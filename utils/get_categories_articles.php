<?php

use App\Query\PronunciationQueryLatvianLanguage;

require 'vendor/autoload.php';

// docker exec -it php-app php utils/get_categories_articles.php bengali

$language = ucfirst($argv[1]);

$queryLatvian = new PronunciationQueryLatvianLanguage();

$queryClassName = "\\App\\Query\\PronunciationQuery{$language}Language";
if (!class_exists($queryClassName)) {
    die("Error: Query class {$queryClassName} does not exist\n");
}
$query = new $queryClassName();


$serviceClassName = "\\App\\Service\\WiktionaryArticlesCategories{$language}Service";
if (!class_exists($serviceClassName)) {
    die("Error: Service class {$serviceClassName} does not exist\n");
}
$categoriesService = new $serviceClassName($queryLatvian, $query);

$categoriesService->getArticlesByCategory();
