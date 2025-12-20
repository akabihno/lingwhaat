<?php

namespace App\Service\Search;

use App\Service\Logging\ElasticsearchLogger;
use Elastica\Client;
use Elastica\Query;
use Elastica\Query\Wildcard;
use Exception;

class PatternSearchService
{
    private Client $esClient;
    private string $indexName = 'words_index';

    public function __construct(
        protected ElasticsearchLogger $logger,
    ) {
        $this->esClient = ElasticsearchClientFactory::create();
    }

    /**
     * Search for words matching a pattern where '?' represents a single unknown character.
     *
     * @param string $pattern Pattern to search (e.g., "h?s?" matches "hose", "hash", etc.)
     * @param string|null $languageCode Optional language filter
     * @param int $limit Maximum number of results to return
     * @return array Array of matching words with their IPA and language code
     */
    public function findByPattern(string $pattern, ?string $languageCode = null, int $limit = 100): array
    {
        if (empty($pattern)) {
            return [];
        }

        try {
            $wildcardQuery = new Wildcard('word', strtolower($pattern));

            $query = new Query\BoolQuery();
            $query->addMust($wildcardQuery);

            if ($languageCode !== null) {
                $languageQuery = new Query\Term();
                $languageQuery->setTerm('languageCode', $languageCode);
                $query->addMust($languageQuery);
            }

            $mainQuery = new Query($query);
            $mainQuery->setSize($limit);

            $this->logger->info('Pattern search initiated', [
                'service' => '[PatternSearchService]',
                'pattern' => $pattern,
                'languageCode' => $languageCode,
                'limit' => $limit,
            ]);

            $results = $this->esClient->getIndex($this->indexName)->search($mainQuery);

            $result = array_map(function ($r) {
                return $r->getSource();
            }, $results->getResults());

            $this->logger->info('Pattern search completed', [
                'service' => '[PatternSearchService]',
                'pattern' => $pattern,
                'result_count' => count($result),
            ]);

            return $result;
        } catch (Exception $e) {
            $this->logger->error('Pattern search failed', [
                'service' => '[PatternSearchService]',
                'pattern' => $pattern,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Search for words matching a pattern in the IPA field.
     *
     * @param string $pattern IPA pattern to search (e.g., "h?s?" for IPA representations)
     * @param string|null $languageCode Optional language filter
     * @param int $limit Maximum number of results to return
     * @return array Array of matching words with their IPA and language code
     */
    public function findByIpaPattern(string $pattern, ?string $languageCode = null, int $limit = 100): array
    {
        if (empty($pattern)) {
            return [];
        }

        try {
            $wildcardQuery = new Wildcard('ipa', strtolower($pattern));

            $query = new Query\BoolQuery();
            $query->addMust($wildcardQuery);

            if ($languageCode !== null) {
                $languageQuery = new Query\Term();
                $languageQuery->setTerm('languageCode', $languageCode);
                $query->addMust($languageQuery);
            }

            $mainQuery = new Query($query);
            $mainQuery->setSize($limit);

            $this->logger->info('IPA pattern search initiated', [
                'service' => '[PatternSearchService]',
                'ipa_pattern' => $pattern,
                'languageCode' => $languageCode,
                'limit' => $limit,
            ]);

            $results = $this->esClient->getIndex($this->indexName)->search($mainQuery);

            $result = array_map(function ($r) {
                return $r->getSource();
            }, $results->getResults());

            $this->logger->info('IPA pattern search completed', [
                'service' => '[PatternSearchService]',
                'ipa_pattern' => $pattern,
                'result_count' => count($result),
            ]);

            return $result;
        } catch (Exception $e) {
            $this->logger->error('IPA pattern search failed', [
                'service' => '[PatternSearchService]',
                'ipa_pattern' => $pattern,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }
}
