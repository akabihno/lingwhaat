<?php

namespace App\Service\Search;

use App\Entity\WikipediaArticleEntity;
use App\Repository\WikipediaArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Elastica\Client;
use InvalidArgumentException;

class WikipediaPatternIndexerService
{
    private const int BASE = 101;
    private const int MOD = 1000000007;
    private const string INDEX_NAME_PREFIX = 'wikipedia_global_patterns';
    private const string WRITE_INDEX_PREFIX = 'wiki_patterns';
    private const int BATCH_SIZE = 5000;
    private const int ARTICLE_FETCH_BATCH_SIZE = 500;

    /**
     * Name of the per-language alias (also the name that the search service queries).
     * Concrete write indices use WRITE_INDEX_PREFIX and are never queried directly.
     */
    public static function indexNameFor(string $languageCode): string
    {
        return self::INDEX_NAME_PREFIX . '_' . $languageCode;
    }

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ElasticsearchBulkStreamer $bulk,
        private readonly Client $esClient,
    ) {
    }

    /**
     * Create a fresh write-target index for a language with bulk-load settings
     * (refresh disabled). Returns the concrete index name; the caller must call
     * promoteToAlias() on success or deleteConcreteIndex() on failure.
     */
    public function prepareWriteIndex(string $languageCode): string
    {
        if ($languageCode === '') {
            throw new InvalidArgumentException('languageCode must be provided.');
        }

        $concreteName = self::WRITE_INDEX_PREFIX . '_' . $languageCode . '_' . time();

        $this->esClient->getIndex($concreteName)->create([
            'settings' => [
                'refresh_interval' => '-1',
            ],
            'mappings' => [
                'properties' => [
                    'pattern_hash'    => ['type' => 'long'],
                    'pattern'         => [
                        'type'   => 'text',
                        'fields' => ['keyword' => ['type' => 'keyword']],
                    ],
                    'global_position' => ['type' => 'long'],
                    'article_id'      => ['type' => 'long'],
                    'local_position'  => ['type' => 'long'],
                    'length'          => ['type' => 'integer'],
                ],
            ],
        ]);

        return $concreteName;
    }

    /**
     * Atomically swap the per-language alias to the new concrete index, restore
     * the default refresh interval, and delete the old concrete index (if any).
     * If the alias name collides with a legacy plain index it is deleted first so
     * the alias can be created (one brief empty-search window on first migration).
     */
    public function promoteToAlias(string $languageCode, string $concreteIndex): void
    {
        if ($languageCode === '') {
            throw new InvalidArgumentException('languageCode must be provided.');
        }

        $aliasName = self::indexNameFor($languageCode);

        // Restore ES default refresh and force a final segment merge before serving.
        $this->bulk->restoreRefreshInterval($concreteIndex);
        $this->bulk->forceRefresh($concreteIndex);

        $oldIndices = $this->bulk->resolveAliasTargets($aliasName);

        if ($oldIndices === []) {
            // First run after migration: a legacy plain index with the alias name may exist.
            $legacy = $this->esClient->getIndex($aliasName);
            if ($legacy->exists()) {
                $legacy->delete();
            }
        }

        // Atomic add-new / remove-old via the _aliases API.
        $this->bulk->swapAlias($aliasName, $concreteIndex, $oldIndices);

        // Best-effort deletion of the detached indices — failures are non-critical.
        foreach ($oldIndices as $old) {
            try {
                $this->esClient->getIndex($old)->delete();
            } catch (\Throwable) {
            }
        }
    }

    /**
     * Remove a concrete write index that was never promoted (error-path cleanup).
     * Silently succeeds if the index does not exist.
     */
    public function deleteConcreteIndex(string $concreteIndex): void
    {
        try {
            $index = $this->esClient->getIndex($concreteIndex);
            if ($index->exists()) {
                $index->delete();
            }
        } catch (\Throwable) {
        }
    }

    /**
     * Full re-index of all articles for a language. Creates a new write index,
     * indexes everything, then promotes the alias atomically.
     * Used by the console command; the async handler uses the batch variant.
     */
    public function indexAllByLanguageCode(int $windowSize, string $languageCode): void
    {
        if ($windowSize <= 0) {
            throw new InvalidArgumentException('windowSize must be greater than 0.');
        }
        if ($languageCode === '') {
            throw new InvalidArgumentException('languageCode must be provided.');
        }

        $targetIndex = $this->prepareWriteIndex($languageCode);
        try {
            $this->doIndex($windowSize, $languageCode, $targetIndex, null, 0);
            $this->promoteToAlias($languageCode, $targetIndex);
        } catch (\Throwable $e) {
            $this->deleteConcreteIndex($targetIndex);
            throw $e;
        }
    }

    /**
     * Index up to $articleLimit articles whose id is greater than $afterId into $targetIndex.
     * Returns ['processed' => <count>, 'lastArticleId' => <id>]; a processed count below
     * $articleLimit means end of data and the caller should reset the cursor to 0, otherwise it
     * should persist lastArticleId to resume the next batch from there.
     *
     * $heartbeat is called after every bulk flush so the caller can refresh a lock TTL
     * or send a keepalive without the service needing to know about either.
     *
     * @return array{processed:int, lastArticleId:int}
     */
    public function indexBatchByLanguageCode(
        int $windowSize,
        string $languageCode,
        string $targetIndex,
        int $articleLimit,
        int $afterId = 0,
        ?callable $heartbeat = null,
    ): array {
        if ($windowSize <= 0) {
            throw new InvalidArgumentException('windowSize must be greater than 0.');
        }
        if ($languageCode === '') {
            throw new InvalidArgumentException('languageCode must be provided.');
        }

        return $this->doIndex($windowSize, $languageCode, $targetIndex, $articleLimit, $afterId, $heartbeat);
    }

    /**
     * @return array{processed:int, lastArticleId:int}
     */
    private function doIndex(
        int $windowSize,
        string $languageCode,
        string $targetIndex,
        ?int $articleLimit,
        int $afterId = 0,
        ?callable $heartbeat = null,
    ): array {
        /** @var WikipediaArticleRepository $repo */
        $repo = $this->em->getRepository(WikipediaArticleEntity::class);

        $globalPos = 0;
        $batch = [];
        $lastArticleId = $afterId;
        $totalArticlesProcessed = 0;

        do {
            $fetchLimit = self::ARTICLE_FETCH_BATCH_SIZE;
            if ($articleLimit !== null) {
                $remaining = $articleLimit - $totalArticlesProcessed;
                if ($remaining <= 0) {
                    break;
                }
                $fetchLimit = min($fetchLimit, $remaining);
            }

            $articles = $repo->findIdAndTextByLanguageCodeAfterId(
                $languageCode,
                $fetchLimit,
                $lastArticleId,
            );

            foreach ($articles as $article) {
                $lastArticleId = $article['id'];
                $normalized = $this->normalize($article['text']);
                $chars = $this->splitChars($normalized);
                $charCount = count($chars);

                if ($charCount < $windowSize) {
                    $globalPos += $charCount;
                    continue;
                }

                $lastWindowStart = $charCount - $windowSize;

                for ($windowStart = 0; $windowStart <= $lastWindowStart; $windowStart++) {
                    $pattern = $this->buildPatternFromChars($chars, $windowStart, $windowSize);

                    $batch[] = [
                        'pattern_hash'    => $this->patternHash($pattern),
                        'pattern'         => implode(',', $pattern),
                        'global_position' => $globalPos + $windowStart + $windowSize - 1,
                        'article_id'      => $article['id'],
                        'local_position'  => $windowStart,
                        'length'          => $windowSize,
                    ];

                    if (count($batch) >= self::BATCH_SIZE) {
                        $this->flushBatch($batch, $targetIndex, $heartbeat);
                    }
                }

                $globalPos += $charCount;
            }

            $totalArticlesProcessed += count($articles);
            $this->em->clear();
        } while ($articles !== []);

        if ($batch !== []) {
            $this->flushBatch($batch, $targetIndex, $heartbeat);
        }

        return ['processed' => $totalArticlesProcessed, 'lastArticleId' => $lastArticleId];
    }

    /**
     * @return string[]
     */
    private function splitChars(string $text): array
    {
        return mb_str_split($text) ?: [];
    }

    /**
     * Build the relative-occurrence pattern for the window at $chars[$start..$start+$length).
     * Each character's ID is its first-appearance rank within that window.
     *
     * @param  string[]  $chars
     * @return int[]
     */
    private function buildPatternFromChars(array $chars, int $start, int $length): array
    {
        $map = [];
        $nextId = 0;
        $pattern = [];
        $end = $start + $length;

        for ($i = $start; $i < $end; $i++) {
            $ch = $chars[$i];
            if (!isset($map[$ch])) {
                $map[$ch] = $nextId++;
            }
            $pattern[] = $map[$ch];
        }

        return $pattern;
    }

    /**
     * @param array<int, array<string, int|string>> $batch
     */
    private function flushBatch(array &$batch, string $targetIndex, ?callable $heartbeat = null): void
    {
        $this->bulk->sendBatch($targetIndex, $batch);
        $batch = [];
        if ($heartbeat !== null) {
            ($heartbeat)();
        }
    }

    /**
     * @param int[] $pattern
     */
    private function patternHash(array $pattern): int
    {
        $hash = 0;
        foreach ($pattern as $value) {
            $hash = (($hash * self::BASE) + $value) % self::MOD;
        }
        return $hash;
    }

    private function normalize(string $s): string
    {
        $s = mb_strtolower($s);
        return preg_replace('/[^\p{L}]+/u', '', $s) ?? '';
    }

}
