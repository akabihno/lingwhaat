<?php

namespace App\Service\Search;

use Elastica\Client;

class ElasticsearchClientFactory
{
    public static function create(string $esHost): Client
    {
        return new Client([
            'hosts' => [$esHost],
            // Cap request duration so an overloaded/unresponsive ES makes calls fail fast (and the
            // message retry) instead of blocking a worker forever — the failure mode that froze the
            // pattern-index consumers. This client is used only for metadata ops (mapping reads,
            // index create/delete, alias swaps), which are sub-second normally; bulk indexing goes
            // through ElasticsearchBulkStreamer's own HTTP client, so it is unaffected.
            'transport_config' => [
                'http_client_options' => [
                    'timeout' => 15,       // idle timeout (s)
                    'max_duration' => 30,  // hard cap on total request time (s)
                ],
            ],
        ]);
    }
}
