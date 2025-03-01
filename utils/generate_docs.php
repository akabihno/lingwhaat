<?php

use App\Service\MarkdownGeneratorService;


$markdownGenerator = new MarkdownGeneratorService($argv);
try {
    $markdownGenerator->generateMarkdown();
} catch (Exception $e) {
    echo $e->getMessage()."\n";
}