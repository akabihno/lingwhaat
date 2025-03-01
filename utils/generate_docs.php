<?php

use App\Service\MarkdownGeneratorService;

require 'vendor/autoload.php';

$markdownGenerator = new MarkdownGeneratorService($argv);
try {
    $markdownGenerator->generateMarkdown();
} catch (Exception $e) {
    echo $e->getMessage()."\n";
}