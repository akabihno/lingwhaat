<?php

namespace App\Service\Search;

use Elastica\Client;

class ElasticsearchClientFactory
{
    public static function create(): Client
    {
        $host = $_ENV['ELASTICSEARCH_HOST'] ?? 'localhost';
        $port = $_ENV['ELASTICSEARCH_PORT'] ?? 9200;

        return new Client([
            'hosts' => ["http://{$host}:{$port}"],
        ]);
    }

}