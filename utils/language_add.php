<?php

use App\Query\LanguageQuery;
use App\Service\LanguageAddService;

require 'vendor/autoload.php';

// docker exec -it php-app php utils/language_add.php kazakh

$language = $argv[1];

$languageQuery = new LanguageQuery();
$languageAddService = new LanguageAddService($languageQuery);

$languageAddService->addLanguage($language);



