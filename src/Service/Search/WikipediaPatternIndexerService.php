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
    public function prepareWriteIndex(string $languageCode, int $windowSize): string
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
                // Tag managed + the windowSize this index was built for, so ensureWriteIndex adopts
                // (reuses) it for incremental batches and rebuilds only when the windowSize changes.
                '_meta'      => ['stable' => true, 'windowSize' => $windowSize],
                'properties' => $this->patternMappingProperties(),
            ],
        ]);

        return $concreteName;
    }

    /**
     * Resolve the stable, incrementally-written index for a language, creating it on first use.
     *
     * Steady state is a no-op beyond one read: if the alias already points at a single managed
     * index, it is reused and the caller writes deltas straight into it — no per-batch index
     * create / alias swap / delete (the operations that thrash the cluster state). The one-time
     * blue-green swap only runs the first time a language is seen or when migrating off a legacy
     * (unmanaged) index left behind by the old rebuild-per-batch handler. A windowSize change is
     * also a migration: the deterministic ids embed windowSize, so old-size docs would never be
     * overwritten — a fresh index drops them in one step instead of leaving phantom matches.
     */
    public function ensureWriteIndex(string $languageCode, int $windowSize): string
    {
        if ($languageCode === '') {
            throw new InvalidArgumentException('languageCode must be provided.');
        }

        $aliasName = self::indexNameFor($languageCode);
        $targets = $this->bulk->resolveAliasTargets($aliasName);

        if (count($targets) === 1 && $this->managedWindowSize($targets[0]) === $windowSize) {
            return $targets[0];
        }

        // First run for this language, migrating off a legacy index, or a windowSize change: build
        // one fresh managed index, swap the alias to it once, and drop the old target(s).
        $stableIndex = $this->createStableIndex($languageCode, $windowSize);

        if ($targets === []) {
            // A legacy plain index may occupy the alias name; remove it so the alias can be created.
            $legacy = $this->esClient->getIndex($aliasName);
            if ($legacy->exists()) {
                $legacy->delete();
            }
        }

        $this->bulk->swapAlias($aliasName, $stableIndex, $targets);

        foreach ($targets as $old) {
            try {
                $this->esClient->getIndex($old)->delete();
            } catch (\Throwable) {
            }
        }

        return $stableIndex;
    }

    /**
     * Delete docs left behind by an earlier indexing pass — those an article no longer produces
     * (it shrank, or was removed from the corpus). After a full pass every current doc carries the
     * current generation; anything still stamped with an older generation is stale and pruned.
     */
    public function pruneStaleGenerations(string $targetIndex, int $currentGeneration): void
    {
        $this->bulk->deleteByGenerationLessThan($targetIndex, $currentGeneration);
    }

    /**
     * Create a long-lived index for incremental writes. Unlike prepareWriteIndex this keeps the
     * default refresh interval (it is continuously written and searched, never bulk-loaded then
     * swapped) and is tagged managed so ensureWriteIndex reuses it on subsequent batches.
     */
    private function createStableIndex(string $languageCode, int $windowSize): string
    {
        $concreteName = self::WRITE_INDEX_PREFIX . '_' . $languageCode . '_' . time();

        $this->esClient->getIndex($concreteName)->create([
            'mappings' => [
                '_meta'      => ['stable' => true, 'windowSize' => $windowSize],
                'properties' => $this->patternMappingProperties(),
            ],
        ]);

        return $concreteName;
    }

    /**
     * The windowSize an index was built for, or null if it is not a managed incremental index
     * (legacy rebuild-per-batch indices, or first-generation managed indices that predate the
     * windowSize tag). A null or mismatching value triggers a one-time rebuild in ensureWriteIndex.
     */
    private function managedWindowSize(string $indexName): ?int
    {
        try {
            // Elastica 8 removed Client::request(); use the Index mapping API. getMapping() returns
            // the contents under "mappings", so _meta is at the top level of the result.
            $meta = $this->esClient->getIndex($indexName)->getMapping()['_meta'] ?? [];
            if (($meta['stable'] ?? false) !== true || !isset($meta['windowSize'])) {
                return null;
            }
            return (int) $meta['windowSize'];
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function patternMappingProperties(): array
    {
        return [
            'pattern_hash'   => ['type' => 'long'],
            'pattern'        => [
                'type'   => 'text',
                'fields' => ['keyword' => ['type' => 'keyword']],
            ],
            'article_id'     => ['type' => 'long'],
            'local_position' => ['type' => 'long'],
            'length'         => ['type' => 'integer'],
            // Indexing pass that last wrote this doc; used to prune stale docs after a full pass.
            'gen'            => ['type' => 'long'],
        ];
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

        $targetIndex = $this->prepareWriteIndex($languageCode, $windowSize);
        try {
            // Fresh index => generation 1; nothing stale can predate it.
            $this->doIndex($windowSize, $languageCode, $targetIndex, null, 0, 1);
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
        int $generation = 1,
        ?callable $heartbeat = null,
    ): array {
        if ($windowSize <= 0) {
            throw new InvalidArgumentException('windowSize must be greater than 0.');
        }
        if ($languageCode === '') {
            throw new InvalidArgumentException('languageCode must be provided.');
        }

        return $this->doIndex($windowSize, $languageCode, $targetIndex, $articleLimit, $afterId, $generation, $heartbeat);
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
        int $generation = 1,
        ?callable $heartbeat = null,
    ): array {
        $repo = $this->em->getRepository(WikipediaArticleEntity::class);

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
                    continue;
                }

                $lastWindowStart = $charCount - $windowSize;

                for ($windowStart = 0; $windowStart <= $lastWindowStart; $windowStart++) {
                    $pattern = $this->buildPatternFromChars($chars, $windowStart, $windowSize);

                    $batch[] = [
                        // Deterministic id => re-indexing an article overwrites its own docs
                        // instead of duplicating them, making incremental writes idempotent.
                        '_id'            => $article['id'] . ':' . $windowStart . ':' . $windowSize,
                        'pattern_hash'   => $this->patternHash($pattern),
                        'pattern'        => implode(',', $pattern),
                        'article_id'     => $article['id'],
                        'local_position' => $windowStart,
                        'length'         => $windowSize,
                        'gen'            => $generation,
                    ];

                    if (count($batch) >= self::BATCH_SIZE) {
                        $this->flushBatch($batch, $targetIndex, $heartbeat);
                    }
                }
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
