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
            $meta = ['_index' => $indexName];
            // A deterministic _id (when supplied by the caller) turns the write into an upsert:
            // re-indexing the same article overwrites its docs instead of appending duplicates,
            // which is what lets us write incrementally into a stable index without rebuilding it.
            if (isset($doc['_id'])) {
                $meta['_id'] = $doc['_id'];
                unset($doc['_id']);
            }
            $body .= json_encode(['index' => $meta]) . "\n";
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
            $detail = is_array($first) ? ($first['index']['error'] ?? $failures) : $failures;
            throw new \RuntimeException('Bulk indexing errors: ' . json_encode($detail));
        }
    }

    /**
     * Delete every doc whose `gen` is below $minGeneration — the docs an earlier indexing pass
     * wrote but the most recent pass did not re-write (shrunk or removed articles). conflicts=proceed
     * so concurrent writes from the in-flight next pass don't abort the prune.
     */
    public function deleteByGenerationLessThan(string $indexName, int $minGeneration): void
    {
        $response = $this->client->request('POST', "{$this->esHost}/{$indexName}/_delete_by_query", [
            'headers' => ['Content-Type' => 'application/json'],
            'query' => ['conflicts' => 'proceed'],
            'body' => json_encode(['query' => ['range' => ['gen' => ['lt' => $minGeneration]]]]),
            'timeout' => $this->timeout,
            'max_duration' => $this->timeout,
        ]);
        $content = $response->getContent(false);
        if ($response->getStatusCode() >= 300) {
            throw new \RuntimeException("Generation prune failed for {$indexName}: {$content}");
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