<?php

namespace App\Service\Search;

use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class WordCategorySearchService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly WordCategoryIndexer $indexer,
        private readonly string $esHost = 'http://localhost:9200',
    ) {
    }

    /**
     * Finds the most semantically similar words in a target language using kNN cosine similarity.
     *
     * @param float[]             $vector            1000-dim category vector from the source word
     * @param string              $targetLanguageCode Language to search in
     * @param int                 $limit             Maximum number of results to return
     *
     * @return array<array{word: string, score: float}>
     *
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function findSimilarByVector(array $vector, string $targetLanguageCode, int $limit = 10): array
    {
        $indexName = WordCategoryIndexer::INDEX_NAME;

        $body = [
            'knn' => [
                'field' => 'categoryVector',
                'query_vector' => $vector,
                'k' => $limit,
                'num_candidates' => max($limit * 10, 100),
                'filter' => [
                    'term' => ['languageCode' => $targetLanguageCode],
                ],
            ],
            '_source' => ['word', 'languageCode'],
            'size' => $limit,
        ];

        $response = $this->httpClient->request(
            'POST',
            "{$this->esHost}/{$indexName}/_search",
            [
                'json' => $body,
                'headers' => ['Content-Type' => 'application/json'],
            ]
        );

        $data = $response->toArray();
        $hits = [];

        foreach ($data['hits']['hits'] ?? [] as $hit) {
            $hits[] = [
                'word' => $hit['_source']['word'],
                'score' => round((float) $hit['_score'], 4),
            ];
        }

        return $hits;
    }

    /**
     * Finds similar words across all languages, grouped by language code.
     *
     * @param float[] $vector
     * @return array<string, array<array{word: string, score: float}>>
     *
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function findSimilarAcrossLanguages(array $vector, array $targetLanguageCodes, int $limitPerLanguage = 5): array
    {
        $indexName = WordCategoryIndexer::INDEX_NAME;
        $totalLimit = count($targetLanguageCodes) * $limitPerLanguage;

        $body = [
            'knn' => [
                'field' => 'categoryVector',
                'query_vector' => $vector,
                'k' => $totalLimit,
                'num_candidates' => max($totalLimit * 10, 100),
                'filter' => [
                    'terms' => ['languageCode' => $targetLanguageCodes],
                ],
            ],
            '_source' => ['word', 'languageCode'],
            'size' => $totalLimit,
        ];

        $response = $this->httpClient->request(
            'POST',
            "{$this->esHost}/{$indexName}/_search",
            [
                'json' => $body,
                'headers' => ['Content-Type' => 'application/json'],
            ]
        );

        $data = $response->toArray();
        $grouped = [];

        foreach ($data['hits']['hits'] ?? [] as $hit) {
            $lang = $hit['_source']['languageCode'];
            if (!isset($grouped[$lang])) {
                $grouped[$lang] = [];
            }
            if (count($grouped[$lang]) < $limitPerLanguage) {
                $grouped[$lang][] = [
                    'word' => $hit['_source']['word'],
                    'score' => round((float) $hit['_score'], 4),
                ];
            }
        }

        return $grouped;
    }
}
