<?php

namespace App\Service\Search;

use App\Repository\WikipediaArticleRepository;
use App\Service\LanguageRepositoryResolver;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class WordsPopularityScoreSetService
{
    private const int BATCH_SIZE = 100;
    private const int FLUSH_EVERY = 500;

    public function __construct(
        private readonly WikipediaArticleRepository $wikipediaArticleRepository,
        private readonly FuzzySearchService         $fuzzySearchService,
        private readonly LanguageRepositoryResolver $languageRepositoryResolver,
        private readonly EntityManagerInterface     $entityManager,
        private readonly LoggerInterface            $logger,
    ) {
    }

    /**
     * Process Wikipedia articles and update word popularity scores
     *
     * @param string $languageCode Target language code
     * @param int $limit Number of articles to process per batch
     * @param int $offset Starting offset
     * @return array Statistics about the processing
     */
    public function execute(string $languageCode, int $limit = self::BATCH_SIZE, int $offset = 0): array
    {
        $languageRepository = $this->languageRepositoryResolver->getRepository($languageCode);

        if (!$languageRepository) {
            $this->logger->warning("[WordsPopularityScoreSetService] No repository found for language code: {$languageCode}");
            return ['processed' => 0, 'matched' => 0, 'error' => 'Repository not found'];
        }

        $articles = $this->wikipediaArticleRepository->findByLanguageCodePaginated($languageCode, $limit, $offset);

        if (empty($articles)) {
            $this->logger->info("[WordsPopularityScoreSetService] No articles found", ['languageCode' => $languageCode, 'offset' => $offset]);
            return ['processed' => 0, 'matched' => 0];
        }

        $processedWords = 0;
        $matchedWords = 0;
        $updateCount = 0;

        foreach ($articles as $article) {
            $text = $article->getText();

            $words = $this->extractWords($text);

            foreach ($words as $word) {
                $processedWords++;

                $matches = $this->fuzzySearchService->findClosestMatches($word, 1, $languageCode);

                if (!empty($matches)) {
                    $matchedWord = $matches[0]['word'] ?? null;

                    if ($matchedWord) {
                        $languageRepository->incrementScoreByName($matchedWord);
                        $matchedWords++;
                        $updateCount++;

                        if ($updateCount % self::FLUSH_EVERY === 0) {
                            $this->entityManager->flush();
                            $this->entityManager->clear();
                            $this->logger->info("Flushed {$updateCount} updates", [
                                'languageCode' => $languageCode,
                                'offset' => $offset
                            ]);
                        }
                    }
                }
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        $stats = [
            'processed' => $processedWords,
            'matched' => $matchedWords,
            'articles' => count($articles),
            'offset' => $offset,
            'limit' => $limit,
        ];

        $this->logger->info('[WordsPopularityScoreSetService] Batch processing completed', array_merge(['languageCode' => $languageCode], $stats));

        return $stats;
    }

    /**
     * Extract and normalize words from text
     */
    private function extractWords(string $text): array
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
        $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);

        return array_filter($words, fn($word) => mb_strlen($word) >= 2);
    }

    /**
     * Get the total count of articles for a language
     */
    public function getTotalArticles(string $languageCode): int
    {
        return $this->wikipediaArticleRepository->countByLanguageCode($languageCode);
    }
}