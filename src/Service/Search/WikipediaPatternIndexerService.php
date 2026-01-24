<?php

namespace App\Service\Search;

use App\Entity\WikipediaArticleEntity;
use Doctrine\ORM\EntityManagerInterface;
use Elastica\Client;

class WikipediaPatternIndexerService
{
    private const int BASE = 101;
    private const int MOD = 1000000007;
    private const int DEFAULT_WINDOW_SIZE = 100;
    private const string INDEX_NAME = 'wikipedia_global_patterns';

    private Client $esClient;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ElasticsearchBulkStreamer $bulk
    ) {
        $this->esClient = ElasticsearchClientFactory::create();
    }

    /**
     * Build a global sliding-window index across ALL Wikipedia articles.
     */
    public function indexAll(int $windowSize = self::DEFAULT_WINDOW_SIZE): void
    {
        if ($windowSize <= 0) {
            throw new \InvalidArgumentException('windowSize must be greater than 0.');
        }

        $index = $this->esClient->getIndex(self::INDEX_NAME);
        if ($index->exists()) {
            $index->delete();
        }

        $repo = $this->em->getRepository(WikipediaArticleEntity::class);
        $articles = $repo->findBy([], ['id' => 'ASC']);

        $globalPos = 0;

        $window = [];

        $batch = [];

        foreach ($articles as $article) {
            $normalized = $this->normalize($article->getText());
            $chars = preg_split('//u', $normalized, -1, PREG_SPLIT_NO_EMPTY);

            foreach ($chars as $ch) {

                $window[] = $ch;

                if (count($window) > $windowSize) {
                    array_shift($window);
                }

                if (count($window) === $windowSize) {
                    $pattern = $this->buildPattern(implode('', $window));
                    $patternHash = $this->patternHash($pattern);

                    $batch[] = [
                        'pattern_hash' => $patternHash,
                        'pattern' => implode(',', $pattern),
                        'global_position' => $globalPos,
                        'length' => $windowSize,
                    ];

                    // Send in batches of 5000
                    if (count($batch) >= 5000) {
                        $this->bulk->sendBatch(self::INDEX_NAME, $batch);
                        $batch = [];
                    }
                }

                $globalPos++;
            }
        }

        // Flush remaining
        if (!empty($batch)) {
            $this->bulk->sendBatch(self::INDEX_NAME, $batch);
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
