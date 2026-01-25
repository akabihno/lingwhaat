<?php

namespace App\Service\Search;

use App\Entity\WikipediaArticleEntity;
use Doctrine\ORM\EntityManagerInterface;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastica\Client;

class WikipediaPatternIndexerService
{
    private const int BASE = 101;
    private const int MOD = 1000000007;
    private const string INDEX_NAME = 'wikipedia_global_patterns';
    private const int BATCH_SIZE = 5000;

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
            throw new \InvalidArgumentException('windowSize must be greater than 0.');
        }

        if (!$languageCode) {
            throw new \InvalidArgumentException('languageCode must be provided.');
        }

        $index = $this->esClient->getIndex(self::INDEX_NAME);
        try {
            if ($index->exists()) {
                $index->delete();
            }
        } catch (\Throwable $e) {
            throw new ClientResponseException($e->getMessage(), $e->getCode(), $e);
        }

        $repo = $this->em->getRepository(WikipediaArticleEntity::class);
        $articles = $repo->findBy(['languageCode' => $languageCode], ['id' => 'ASC']);

        $globalPos = 0;

        $window = [];
        $windowMeta = [];

        $batch = [];

        foreach ($articles as $article) {
            $normalized = $this->normalize($article->getText());
            $chars = preg_split('//u', $normalized, -1, PREG_SPLIT_NO_EMPTY);
            $localPos = 0;

            foreach ($chars as $ch) {

                $window[] = $ch;
                $windowMeta[] = ['article_id' => $article->getId(), 'local_position' => $localPos];
                $localPos++;

                if (count($window) > $windowSize) {
                    array_shift($window);
                    array_shift($windowMeta);
                }

                if (count($window) === $windowSize) {
                    $pattern = $this->buildPattern(implode('', $window));
                    $patternHash = $this->patternHash($pattern);
                    $startMeta = $windowMeta[0];

                    $batch[] = [
                        'pattern_hash' => $patternHash,
                        'pattern' => implode(',', $pattern),
                        'global_position' => $globalPos,
                        'article_id' => $startMeta['article_id'],
                        'local_position' => $startMeta['local_position'],
                        'length' => $windowSize,
                    ];

                    if (count($batch) >= self::BATCH_SIZE) {
                        try {
                            $this->bulk->sendBatch(self::INDEX_NAME, $batch);
                        } catch (\Throwable $e) {
                            throw new ClientResponseException($e->getMessage(), $e->getCode(), $e);
                        }
                        $batch = [];
                    }
                }

                $globalPos++;
            }
        }

        if (!empty($batch)) {
            try {
                $this->bulk->sendBatch(self::INDEX_NAME, $batch);
            } catch (\Throwable $e) {
                throw new ClientResponseException($e->getMessage(), $e->getCode(), $e);
            }
        }
    }

    private function buildPattern(string $s): array
    {
        $map = [];
        $nextId = 0;
        $pattern = [];

        foreach (preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY) as $ch) {
            if (!isset($map[$ch])) {
                $map[$ch] = $nextId++;
            }
            $pattern[] = $map[$ch];
        }

        return $pattern;
    }

    private function patternHash(array $pattern): int
    {
        $m = count($pattern);
        $hash = 0;

        for ($i = 0; $i < $m; $i++) {
            $power = $m - 1 - $i;
            $hash = ($hash + $pattern[$i] * $this->powmod(self::BASE, $power)) % self::MOD;
        }

        return $hash;
    }

    private function powmod(int $base, int $exp): int
    {
        $result = 1;
        $base %= self::MOD;

        while ($exp > 0) {
            if ($exp & 1) {
                $result = ($result * $base) % self::MOD;
            }
            $base = ($base * $base) % self::MOD;
            $exp >>= 1;
        }

        return $result;
    }

    private function normalize(string $s): string
    {
        $s = mb_strtolower($s);
        return preg_replace('/[^\p{L}]+/u', '', $s);
    }
}
