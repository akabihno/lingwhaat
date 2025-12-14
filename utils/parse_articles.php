<?php

require 'vendor/autoload.php';

// deprecated, now managed by src/Schedule.php
// watch --interval 360 docker exec -it php-app php utils/parse_articles.php icelandic 300

use App\Query\AbstractQuery;
use App\Service\Logging\ElasticsearchLogger;
use App\Service\WiktionaryArticlesIpaParserService;
use Elastica\Client;

$language = $argv[1];
$limit = $argv[2] ?? 100;

$abstractQuery = new AbstractQuery();

$client = new Client();
$indexPrefix = 'application-logs';
$logger = new ElasticsearchLogger($client, $indexPrefix);
$wiktionaryArticlesService = new WiktionaryArticlesIpaParserService($abstractQuery, $logger);

$wiktionaryArticlesService->run(strtolower($language), $limit);
