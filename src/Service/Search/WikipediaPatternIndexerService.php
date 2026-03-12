<?php

namespace App\Service\Search;

use App\Entity\WikipediaArticleEntity;
use App\Repository\WikipediaArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastica\Client;
use Generator;
use InvalidArgumentException;
use SplQueue;

class WikipediaPatternIndexerService
{
    private const int BASE = 101;
    private const int MOD = 1000000007;
    private const string INDEX_NAME = 'wikipedia_global_patterns';
    private const int BATCH_SIZE = 5000;
    private const int ARTICLE_FETCH_BATCH_SIZE = 100;

    private Client $esClient;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ElasticsearchBulkStreamer $bulk
    ) {
        $this->esClient = ElasticsearchClientFactory::create();
    }

    /**
     * Build a global sliding-window index across ALL Wikipedia articles.
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

        /** @var WikipediaArticleRepository $repo */
        $repo = $this->em->getRepository(WikipediaArticleEntity::class);

        $globalPos = 0;
        $batch = [];
        $windowChars = new SplQueue();
        $windowMeta = new SplQueue();
        $offset = 0;

        do {
            $articles = $repo->findIdAndTextByLanguageCodePaginated(
                $languageCode,
                self::ARTICLE_FETCH_BATCH_SIZE,
                $offset
            );

            foreach ($articles as $article) {
                $normalized = $this->normalize($article['text']);
                $localPos = 0;

                foreach ($this->iterateChars($normalized) as $char) {
                    $windowChars->enqueue($char);
                    $windowMeta->enqueue([
                        'article_id' => $article['id'],
                        'local_position' => $localPos,
                    ]);
                    $localPos++;

                    if ($windowChars->count() > $windowSize) {
                        $windowChars->dequeue();
                        $windowMeta->dequeue();
                    }

                    if ($windowChars->count() === $windowSize) {
                        $pattern = $this->buildPatternFromChars(iterator_to_array($windowChars, false));
                        $startMeta = $windowMeta->bottom();

                        $batch[] = [
                            'pattern_hash' => $this->patternHash($pattern),
                            'pattern' => implode(',', $pattern),
                            'global_position' => $globalPos,
                            'article_id' => $startMeta['article_id'],
                            'local_position' => $startMeta['local_position'],
                            'length' => $windowSize,
                        ];

                        if (count($batch) >= self::BATCH_SIZE) {
                            $this->flushBatch($batch);
                        }
                    }

                    $globalPos++;
                }
            }

            $offset += count($articles);
            $this->em->clear();
        } while ($articles !== []);

        if ($batch !== []) {
            $this->flushBatch($batch);
        }
    }

    /**
     * @return Generator<int, string>
     */
    private function iterateChars(string $text): Generator
    {
        $length = mb_strlen($text);

        for ($i = 0; $i < $length; $i++) {
            yield mb_substr($text, $i, 1);
        }
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