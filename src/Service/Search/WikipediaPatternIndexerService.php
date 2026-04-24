<?php

namespace App\Service\Search;

use App\Entity\WikipediaArticleEntity;
use App\Repository\WikipediaArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastica\Client;
use InvalidArgumentException;

class WikipediaPatternIndexerService
{
    private const int BASE = 101;
    private const int MOD = 1000000007;
    private const string INDEX_NAME = 'wikipedia_global_patterns';
    private const int BATCH_SIZE = 5000;
    private const int ARTICLE_FETCH_BATCH_SIZE = 500;

    private Client $esClient;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ElasticsearchBulkStreamer $bulk,
        Client $esClient,
    ) {
        $this->esClient = $esClient;
    }

    /**
     * Delete the global patterns index if it exists.
     * @throws ClientResponseException
     */
    public function deleteIndex(): void
    {
        $index = $this->esClient->getIndex(self::INDEX_NAME);
        try {
            if ($index->exists()) {
                $index->delete();
            }
        } catch (\Throwable $e) {
            throw new ClientResponseException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Build a global sliding-window index across ALL Wikipedia articles for a language.
     * Deletes and recreates the entire index.
     * @throws ClientResponseException
     */
    public function indexAllByLanguageCode(int $windowSize, string $languageCode): void
    {
        if ($windowSize <= 0) {
            throw new InvalidArgumentException('windowSize must be greater than 0.');
        }

        if ($languageCode === '') {
            throw new InvalidArgumentException('languageCode must be provided.');
        }

        $index = $this->esClient->getIndex(self::INDEX_NAME);
        try {
            if ($index->exists()) {
                $index->delete();
            }
        } catch (\Throwable $e) {
            throw new ClientResponseException($e->getMessage(), $e->getCode(), $e);
        }

        $this->doIndex($windowSize, $languageCode, null, 0);
    }

    /**
     * Index up to $articleLimit articles for a language starting from $startOffset into an existing index (no deletion).
     * Returns the number of articles actually fetched (< $articleLimit means end of data, caller should reset offset).
     * @throws ClientResponseException
     */
    public function indexBatchByLanguageCode(int $windowSize, string $languageCode, int $articleLimit, int $startOffset = 0): int
    {
        if ($windowSize <= 0) {
            throw new InvalidArgumentException('windowSize must be greater than 0.');
        }

        if ($languageCode === '') {
            throw new InvalidArgumentException('languageCode must be provided.');
        }

        return $this->doIndex($windowSize, $languageCode, $articleLimit, $startOffset);
    }

    /**
     * @throws ClientResponseException
     */
    private function doIndex(int $windowSize, string $languageCode, ?int $articleLimit, int $startOffset = 0): int
    {
        /** @var WikipediaArticleRepository $repo */
        $repo = $this->em->getRepository(WikipediaArticleEntity::class);

        $globalPos = 0;
        $batch = [];
        $offset = $startOffset;
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

            $articles = $repo->findIdAndTextByLanguageCodePaginated(
                $languageCode,
                $fetchLimit,
                $offset
            );

            foreach ($articles as $article) {
                $normalized = $this->normalize($article['text']);
                $chars = $this->splitChars($normalized);
                $charCount = count($chars);

                if ($charCount < $windowSize) {
                    $globalPos += $charCount;
                    continue;
                }

                $lastWindowStart = $charCount - $windowSize;

                for ($windowStart = 0; $windowStart <= $lastWindowStart; $windowStart++) {
                    $windowChars = array_slice($chars, $windowStart, $windowSize);
                    $pattern = $this->buildPatternFromChars($windowChars);

                    $batch[] = [
                        'pattern_hash' => $this->patternHash($pattern),
                        'pattern' => implode(',', $pattern),
                        'global_position' => $globalPos + $windowStart + $windowSize - 1,
                        'article_id' => $article['id'],
                        'local_position' => $windowStart,
                        'length' => $windowSize,
                    ];

                    if (count($batch) >= self::BATCH_SIZE) {
                        $this->flushBatch($batch);
                    }
                }

                $globalPos += $charCount;
            }

            $totalArticlesProcessed += count($articles);
            $offset += count($articles);
            $this->em->clear();
        } while ($articles !== []);

        if ($batch !== []) {
            $this->flushBatch($batch);
        }

        return $totalArticlesProcessed;
    }

    /**
     * @return array<int, string>
     */
    private function splitChars(string $text): array
    {
        return preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    /**
     * @param array<int, string> $chars
     * @return array<int, int>
     */
    private function buildPatternFromChars(array $chars): array
    {
        $map = [];
        $nextId = 0;
        $pattern = [];

        foreach ($chars as $char) {
            if (!isset($map[$char])) {
                $map[$char] = $nextId++;
            }

            $pattern[] = $map[$char];
        }

        return $pattern;
    }

    /**
     * @param array<int, array<string, int|string>> $batch
     * @throws ClientResponseException
     */
    private function flushBatch(array &$batch): void
    {
        try {
            $this->bulk->sendBatch(self::INDEX_NAME, $batch);
        } catch (\Throwable $e) {
            throw new ClientResponseException($e->getMessage(), $e->getCode(), $e);
        }

        $batch = [];
    }

    /**
     * @param array<int, int> $pattern
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