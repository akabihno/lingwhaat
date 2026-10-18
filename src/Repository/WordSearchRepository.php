<?php

namespace App\Repository;

use App\Entity\WordSearchEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Reads of word_search, the MySQL replacement for the words_index ES index. Results use the
 * words_index document shape (word, ipa, languageCode, score, pattern, wordLength) so callers can
 * switch backends without changes.
 *
 * Where Elasticsearch returned score ties in index order, ties are broken by source_id (newest
 * first, which lets the score indexes serve the ORDER BY with a backward scan).
 */
class WordSearchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WordSearchEntity::class);
    }

    /**
     * Entries of a language whose canonical pattern equals $pattern, most popular first.
     *
     * @return list<array{word: string, ipa: string, languageCode: string, score: int, pattern: string, wordLength: int}>
     */
    public function findByPattern(string $pattern, string $languageCode, int $limit): array
    {
        $rows = $this->getEntityManager()->getConnection()->fetchAllAssociative(
            'SELECT word, ipa, language_code, score, pattern, word_length
             FROM word_search
             WHERE language_code = :languageCode AND pattern_crc = :crc AND pattern = :pattern
             ORDER BY score DESC, source_id DESC
             LIMIT :limit',
            ['languageCode' => $languageCode, 'crc' => crc32($pattern), 'pattern' => $pattern, 'limit' => $limit],
            ['limit' => \PDO::PARAM_INT],
        );

        return array_map(self::toDocument(...), $rows);
    }

    /**
     * The $limit most popular words of a language, most popular first.
     *
     * @return list<string>
     */
    public function findTopWords(string $languageCode, int $limit): array
    {
        return $this->getEntityManager()->getConnection()->fetchFirstColumn(
            'SELECT word FROM word_search WHERE language_code = :languageCode ORDER BY score DESC, source_id DESC LIMIT :limit',
            ['languageCode' => $languageCode, 'limit' => $limit],
            ['limit' => \PDO::PARAM_INT],
        );
    }

    /**
     * Copy scores from a language's pronunciation table for the rows matching $names, after they
     * were updated there. Matching goes through the source table (its collation, its
     * `name IN (...)`), so exactly the rows the source update touched are synced.
     *
     * @param list<string> $names
     */
    public function syncScoresFromSource(string $languageCode, string $sourceTable, array $names): void
    {
        if ($names === []) {
            return;
        }

        $this->getEntityManager()->getConnection()->executeStatement(
            "UPDATE word_search ws
             JOIN {$sourceTable} src ON src.id = ws.source_id
             SET ws.score = src.score
             WHERE ws.language_code = :languageCode AND src.name IN (:names)",
            ['languageCode' => $languageCode, 'names' => $names],
            ['names' => ArrayParameterType::STRING],
        );
    }

    /**
     * @param array<string, mixed> $row
     * @return array{word: string, ipa: string, languageCode: string, score: int, pattern: string, wordLength: int}
     */
    private static function toDocument(array $row): array
    {
        return [
            'word'         => (string) $row['word'],
            'ipa'          => (string) $row['ipa'],
            'languageCode' => (string) $row['language_code'],
            'score'        => (int) $row['score'],
            'pattern'      => (string) $row['pattern'],
            'wordLength'   => (int) $row['word_length'],
        ];
    }
}
