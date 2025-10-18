<?php

namespace App\Service\Search;

use Elastica\Client;
use Elastica\Query;
use Elastica\Query\Fuzzy;

class FuzzySearchService
{
    private Client $esClient;
    private string $indexName = 'words_index';

    public function __construct()
    {
        $this->esClient = ElasticsearchClientFactory::create();
    }

    public function findClosestMatches(string $input, int $limit = 5): array
    {
        $fuzzy = new Fuzzy();
        $fuzzy->setParam('word', [
            'value' => $input,
            'fuzziness' => 1,
            'prefix_length' => 1,
        ]);

        $query = new Query($fuzzy);
        $query->setSize($limit);

        $results = $this->esClient->getIndex($this->indexName)->search($query);

        return array_map(fn($r) => $r->getSource(), $results->getResults());
    }
}
