<?php

use App\Query\NewLanguageQuery;
use App\Service\LanguageAddService;

require 'vendor/autoload.php';

// docker exec -it php-app php utils/language_add.php kazakh

$language = $argv[1];

$newLanguageQuery = new NewLanguageQuery();
$languageAddService = new LanguageAddService($newLanguageQuery);

$languageAddService->addLanguage(strtolower($language));



