<?php

namespace App\Service\Search;

use Elastica\Client;

class ElasticsearchClientFactory
{
    public static function create(string $esHost): Client
    {
        return new Client([
            'hosts' => [$esHost],
        ]);
    }
}
