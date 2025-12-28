<?php

use App\Query\AbstractQuery;
use App\Service\MarkdownGeneratorService;

require 'vendor/autoload.php';

// docker exec -it php-app php utils/generate_docs_paginated.php vietnamese

$language = $argv[1];

$abstractQuery = new AbstractQuery();
$markdownGenerator = new MarkdownGeneratorService($abstractQuery);

$markdownGenerator->generateMarkdown(strtolower($language), false, true);