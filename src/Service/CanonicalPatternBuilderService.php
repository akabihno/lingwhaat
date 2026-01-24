<?php

namespace App\Service;

use App\Entity\WikipediaArticleEntity;
use App\Entity\WikipediaCanonicalPatternEntity;
use App\Service\LanguageDetection\ScriptDetectionService;
use Doctrine\ORM\EntityManagerInterface;

class CanonicalPatternBuilderService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ScriptDetectionService $scriptDetectionService
    ) {
    }

    /**
     * Build patterns for all Wikipedia articles where processed = 0.
     */
    public function processPendingArticles(int $limit = 50): int
    {
        $repo = $this->entityManager->getRepository(WikipediaArticleEntity::class);

        $articles = $repo->findBy(['processed' => 0], null, $limit);
        $count = 0;

        foreach ($articles as $article) {
            $rawText = $article->getText();
            $normalized = $this->normalize($rawText);

            if ($normalized === '') {
                $article->setProcessed(1);
                continue;
            }

            $patternArr = $this->buildPattern($normalized);
            $patternStr = implode(',', $patternArr);
            $patternHash = $this->patternHash($patternArr);

            $script = $this->scriptDetectionService->detect($normalized);

            $entity = new WikipediaCanonicalPatternEntity();
            $entity->setArticle($article);
            $entity->setPattern($patternStr);
            $entity->setPatternHash($patternHash);
            $entity->setScript($script);
            $entity->setTsCreated(date('Y-m-d H:i:s'));

            $this->entityManager->persist($entity);

            $article->setProcessed(1);
            $count++;

            if ($count % 50 === 0) {
                $this->entityManager->flush();
            }
        }

        if ($count > 0) {
            $this->entityManager->flush();
        }

        return $count;
    }

    private function buildPattern(string $s): array
    {
        $map = [];
        $nextId = 0;
        $pattern = [];
        $chars = preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($chars as $ch) {
            if (!array_key_exists($ch, $map)) {
                $map[$ch] = $nextId++;
            }
            $pattern[] = $map[$ch];
        }
        return $pattern;
    }

    private function patternHash(array $pattern, int $base = 101, int $mod = 1000000007): int
    {
        $m = count($pattern);
        $hash = 0;
        for ($i = 0; $i < $m; $i++) {
            $power = $m - 1 - $i;
            $hash = ($hash + $pattern[$i] * $this->powmod($base, $power, $mod)) % $mod;
        }
        return $hash;
    }

    private function powmod(int $base, int $exp, int $mod): int
    {
        $result = 1;
        $base = $base % $mod;
        while ($exp > 0) {
            if ($exp & 1) {
                $result = ($result * $base) % $mod;
            }
            $base = ($base * $base) % $mod;
            $exp >>= 1;
        }
        return $result;
    }

    private function normalize(string $s): string
    {
        $s = mb_strtolower($s);
        $s = preg_replace('/[^\p{L}]+/u', '', $s);
        return $s;
    }
}