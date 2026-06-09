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

    public function restoreRefreshInterval(string $indexName): void
    {
        $response = $this->client->request('PUT', "{$this->esHost}/{$indexName}/_settings", [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode(['refresh_interval' => null]),
            'timeout' => 30,
        ]);
        $content = $response->getContent(false);
        if ($response->getStatusCode() >= 300) {
            throw new \RuntimeException("Failed to restore refresh interval for {$indexName}: {$content}");
        }
    }

    public function forceRefresh(string $indexName): void
    {
        $response = $this->client->request('POST', "{$this->esHost}/{$indexName}/_refresh", [
            'timeout' => 30,
        ]);
        $content = $response->getContent(false);
        if ($response->getStatusCode() >= 300) {
            throw new \RuntimeException("Failed to refresh index {$indexName}: {$content}");
        }
    }

    /**
     * Returns the concrete index names currently pointed to by $aliasName.
     * Returns [] if the alias does not exist or the request fails.
     *
     * @return string[]
     */
    public function resolveAliasTargets(string $aliasName): array
    {
        try {
            $response = $this->client->request('GET', "{$this->esHost}/_alias/{$aliasName}", [
                'timeout' => 10,
            ]);
            if ($response->getStatusCode() === 404) {
                return [];
            }
            $data = json_decode($response->getContent(false), true);
            return array_keys($data ?? []);
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Atomically add $newIndex to $aliasName and remove all $oldIndices from it in one request.
     *
     * @param string[] $oldIndices
     */
    public function swapAlias(string $aliasName, string $newIndex, array $oldIndices): void
    {
        $actions = [['add' => ['index' => $newIndex, 'alias' => $aliasName]]];
        foreach ($oldIndices as $old) {
            $actions[] = ['remove' => ['index' => $old, 'alias' => $aliasName]];
        }

        $response = $this->client->request('POST', "{$this->esHost}/_aliases", [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode(['actions' => $actions]),
            'timeout' => 30,
        ]);
        $content = $response->getContent(false);
        if ($response->getStatusCode() >= 300) {
            throw new \RuntimeException("Alias swap failed for {$aliasName}: {$content}");
        }
    }
}