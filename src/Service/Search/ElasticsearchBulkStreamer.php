<?php

namespace App\Service\Search;

use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ElasticsearchBulkStreamer
{
    public function __construct(
        protected HttpClientInterface $client,
        protected string $esHost = 'http://localhost:9200',
        protected int $timeout = 300,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function sendBatch(string $indexName, array $docs): void
    {
        $body = '';

        foreach ($docs as $doc) {
            $body .= json_encode(['index' => ['_index' => $indexName]]) . "\n";
            $body .= json_encode($doc) . "\n";
        }

        $response = $this->client->request('POST', "{$this->esHost}/_bulk", [
            'body' => $body,
            'headers' => ['Content-Type' => 'application/x-ndjson'],
            'timeout' => $this->timeout,
            'max_duration' => $this->timeout,
        ]);

        $content = $response->getContent(false);

        if ($response->getStatusCode() >= 300) {
            throw new \RuntimeException('Bulk indexing failed: ' . $content);
        }

        $decoded = json_decode($content, true);
        if (!empty($decoded['errors'])) {
            $failures = array_filter($decoded['items'] ?? [], fn($item) => isset($item['index']['error']));
            $first = reset($failures);
            throw new \RuntimeException('Bulk indexing errors: ' . json_encode($first['index']['error'] ?? $failures));
        }
    }


}