<?php

namespace App\Service\Search;

use App\Repository\WikipediaArticleRepository;
use App\Repository\WordsPopularityScoreSetScheduleRepository;
use App\Service\LanguageRepositoryResolver;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class WordsPopularityScoreSetService
{
    private const int BATCH_SIZE = 100;
    private const int WORD_LOOKUP_BATCH_SIZE = 100;

    public function __construct(
        private readonly WikipediaArticleRepository $wikipediaArticleRepository,
        private readonly FuzzySearchService         $fuzzySearchService,
        private readonly LanguageRepositoryResolver $languageRepositoryResolver,
        private readonly WordsPopularityScoreSetScheduleRepository $wordsPopularityScoreSetScheduleRepository,
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

        // Collect all words with their counts
        $wordCounts = [];
        foreach ($articles as $article) {
            $text = $article->getText();
            $words = $this->extractWords($text);

            foreach ($words as $word) {
                $processedWords++;
                $wordCounts[$word] = ($wordCounts[$word] ?? 0) + 1;
            }
        }

        // Process unique words in batches
        $uniqueWords = array_keys($wordCounts);
        $wordBatches = array_chunk($uniqueWords, self::WORD_LOOKUP_BATCH_SIZE);

        // Cache for word matches to avoid duplicate lookups
        $wordMatchCache = [];

        foreach ($wordBatches as $batchWords) {
            // Batch lookup in Elasticsearch
            foreach ($batchWords as $word) {
                if (!isset($wordMatchCache[$word])) {
                    $matches = $this->fuzzySearchService->findClosestMatches($word, 1, $languageCode);
                    $wordMatchCache[$word] = !empty($matches) ? ($matches[0]['word'] ?? null) : null;
                }
            }
        }

        // Aggregate score increments per matched word
        $scoreIncrements = [];
        foreach ($wordCounts as $word => $count) {
            $matchedWord = $wordMatchCache[$word] ?? null;
            if ($matchedWord) {
                $scoreIncrements[$matchedWord] = ($scoreIncrements[$matchedWord] ?? 0) + $count;
                $matchedWords += $count;
            }
        }

        // Bulk update scores using raw SQL for performance
        if (!empty($scoreIncrements)) {
            $this->bulkUpdateScores($languageRepository, $scoreIncrements);
            $this->logger->info("Bulk updated scores for {$processedWords} words", [
                'languageCode' => $languageCode,
                'uniqueWords' => count($uniqueWords),
                'matchedUniqueWords' => count($scoreIncrements),
                'offset' => $offset
            ]);
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

        $this->wordsPopularityScoreSetScheduleRepository->incrementOffsetByLanguageCode($languageCode, $limit);

        return $stats;
    }

    /**
     * Bulk update scores for multiple words using raw SQL
     *
     * @param object $languageRepository The language repository
     * @param array<string, int> $scoreIncrements Map of word names to score increments
     */
    private function bulkUpdateScores(object $languageRepository, array $scoreIncrements): void
    {
        $connection = $this->entityManager->getConnection();
        $tableName = $this->entityManager->getClassMetadata(get_class($languageRepository->find(1) ?? new \stdClass()))->getTableName();

        // Build CASE statement for bulk update
        $caseStatements = [];
        $params = [];
        $types = [];
        $names = [];

        $i = 0;
        foreach ($scoreIncrements as $name => $increment) {
            $caseStatements[] = "WHEN name = :name{$i} THEN score + :inc{$i}";
            $params["name{$i}"] = $name;
            $params["inc{$i}"] = $increment;
            $types["name{$i}"] = \PDO::PARAM_STR;
            $types["inc{$i}"] = \PDO::PARAM_INT;
            $names[] = ":name{$i}";
            $i++;
        }

        if (empty($caseStatements)) {
            return;
        }

        $caseString = implode(' ', $caseStatements);
        $namesString = implode(', ', $names);

        $sql = "UPDATE {$tableName}
                SET score = CASE
                    {$caseString}
                    ELSE score
                END
                WHERE name IN ({$namesString})";

        $connection->executeStatement($sql, $params, $types);
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