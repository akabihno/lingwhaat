<?php

namespace App\Service\Search;

use App\Service\Logging\ElasticsearchLogger;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Elastica\Client;
use Elastica\Query;
use Elastica\Query\BoolQuery;
use Elastica\Query\Fuzzy;
use Elastica\Query\Term;

class FuzzySearchService
{
    private Client $esClient;
    private string $indexName = 'words_index';

    public function __construct(
        protected ElasticsearchLogger $logger,
    )
    {
        $this->esClient = ElasticsearchClientFactory::create();
    }

    /**
     * @throws ServerResponseException
     * @throws ClientResponseException
     */
    public function findExactMatches(string $input, int $limit = 5): array
    {
        $term = new Term();
        $term->setTerm('word', $input);

        $query = new Query($term);
        $query->setSize($limit);

        $results = $this->esClient->getIndex($this->indexName)->search($query);

        return array_map(fn($r) => $r->getSource(), $results->getResults());
    }

    public function findClosestMatches(string $input, int $limit = 5, string $languageCode = ''): array
    {
        $fuzzy = new Fuzzy();
        $fuzzy->setParam('word', [
            'value' => $input,
            'fuzziness' => 1,
            'prefix_length' => 1,
        ]);

        if (!empty($languageCode)) {
            $boolQuery = new BoolQuery();
            $boolQuery->addMust($fuzzy);

            $languageTerm = new Term();
            $languageTerm->setTerm('languageCode', $languageCode);
            $boolQuery->addFilter($languageTerm);

            $query = new Query($boolQuery);
        } else {
            $query = new Query($fuzzy);
        }

        $query->setSize($limit);
        // TODO let's see how it goes,
        // this should affect incorrect language detections
        $query->setSort([
            '_score' => ['order' => 'desc'],
            'score'  => ['order' => 'desc'],
        ]);

        $results = $this->esClient->getIndex($this->indexName)->search($query);

        return array_map(fn($r) => $r->getSource(), $results->getResults());
    }

    public function findExactMatchesByIpa(string $ipa, int $limit = 5): array
    {
        $term = new Term();
        $term->setTerm('ipa', $ipa);

        $query = new Query($term);
        $query->setSize($limit);

        $results = $this->esClient->getIndex($this->indexName)->search($query);

        return array_map(fn($r) => $r->getSource(), $results->getResults());
    }

    public function findClosestMatchesByIpa(string $ipa, int $limit = 5): array
    {
        $fuzzy = new Fuzzy();
        $fuzzy->setParam('ipa', [
            'value' => $ipa,
            'fuzziness' => 1,
            'prefix_length' => 1,
        ]);

        $query = new Query($fuzzy);
        $query->setSize($limit);

        $results = $this->esClient->getIndex($this->indexName)->search($query);

        return array_map(fn($r) => $r->getSource(), $results->getResults());
    }
}
