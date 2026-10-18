<?php

namespace App\Service\Search;

use App\Constant\WordSearchBackend;
use App\Entity\WordSearchTokenEntity;
use App\Repository\WordSearchRepository;
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
    // Hits per exact query on Elasticsearch; MySQL returns every matching language instead.
    private const int ES_EXACT_LIMIT = 5;

    private Client $esClient;
    private string $indexName = 'words_index';

    public function __construct(
        protected ElasticsearchLogger $logger,
        Client $esClient,
        private readonly WordSearchRepository $wordSearchRepository,
        private readonly MysqlWordLookup $mysqlLookup,
        private readonly WordSearchShadow $shadow,
        private readonly string $wordSearchBackend = WordSearchBackend::ELASTICSEARCH,
    ) {
        $this->esClient = $esClient;
    }

    /**
     * @throws ServerResponseException
     * @throws ClientResponseException
     */
    /**
     * Entries containing $input as a token. Elasticsearch returns up to $limit (default 5) entries;
     * MySQL returns the best entry of every matching language, capped only by an explicit $limit.
     */
    public function findExactMatches(string $input, ?int $limit = null, ?callable $onMysqlResult = null): array
    {
        return $this->shadow->serve(
            WordSearchShadow::SHAPE_EXACT_WORD,
            ['input' => $input, 'limit' => $limit],
            fn () => $this->esFindExactMatches($input, $limit ?? self::ES_EXACT_LIMIT),
            fn () => $this->mysqlLookup->exactMatches(WordSearchTokenEntity::FIELD_WORD, $input, $limit),
            fn (array $es, array $mysql) => WordSearchShadow::compareExact($es, $mysql, $limit ?? self::ES_EXACT_LIMIT),
            onMysqlResult: $onMysqlResult,
        );
    }

    private function esFindExactMatches(string $input, int $limit): array
    {
        $term = new Term();
        $term->setTerm('word', $input);

        $query = new Query($term);
        $query->setSize($limit);

        $results = $this->esClient->getIndex($this->indexName)->search($query);

        return array_map(fn($r) => $r->getSource(), $results->getResults());
    }

    public function findClosestMatches(string $input, int $limit = 5, string $languageCode = '', ?callable $onMysqlResult = null): array
    {
        return $this->shadow->serve(
            WordSearchShadow::SHAPE_FUZZY_WORD,
            ['input' => $input, 'limit' => $limit, 'languageCode' => $languageCode],
            fn () => $this->esFindClosestMatches($input, $limit, $languageCode),
            fn () => $this->mysqlLookup->closestMatches(WordSearchTokenEntity::FIELD_WORD, $input, $limit, $languageCode !== '' ? $languageCode : null),
            WordSearchShadow::compareFuzzy(...),
            onMysqlResult: $onMysqlResult,
        );
    }

    private function esFindClosestMatches(string $input, int $limit, string $languageCode): array
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



    /**
     * @return array<int, array<string, mixed>>
     */
    public function findByPattern(string $pattern, string $languageCode, int $limit = 30): array
    {
        if ($this->wordSearchBackend === WordSearchBackend::MYSQL) {
            return $this->wordSearchRepository->findByPattern($pattern, $languageCode, $limit);
        }

        $boolQuery = new BoolQuery();

        $patternTerm = new Term();
        $patternTerm->setTerm('pattern', $pattern);
        $boolQuery->addFilter($patternTerm);

        $languageTerm = new Term();
        $languageTerm->setTerm('languageCode', $languageCode);
        $boolQuery->addFilter($languageTerm);

        $query = new Query($boolQuery);
        $query->setSize($limit);
        $query->setSort([
            'score' => ['order' => 'desc'],
        ]);

        $results = $this->esClient->getIndex($this->indexName)->search($query);

        return array_map(fn($r) => $r->getSource(), $results->getResults());
    }
}
