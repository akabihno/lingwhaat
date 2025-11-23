<?php

use App\Query\AbstractQuery;
use App\Service\MarkdownGeneratorService;

require 'vendor/autoload.php';

// docker exec -it php-app php utils/generate_docs.php serbocroatian
// docker exec -it php-app php utils/generate_docs.php hebrew true

$language = $argv[1];
$rightToLeft = $argv[2] ?? false;

$abstractQuery = new AbstractQuery();
$markdownGenerator = new MarkdownGeneratorService($abstractQuery);

$markdownGenerator->generateMarkdown(strtolower($language), $rightToLeft);