<?php

namespace App\Repository;

use App\Entity\WordSearchEntity;
use App\Entity\WordSearchTokenEntity;
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
     * Distinct tokens of a fuzzy bucket: same first character, length within [$minLength, $maxLength].
     *
     * @return list<string>
     */
    public function findBucketTokens(int $field, string $prefix, int $minLength, int $maxLength, ?string $languageCode): array
    {
        $sql = 'SELECT DISTINCT token FROM word_search_token WHERE field = :field AND token_prefix = :prefix AND token_length BETWEEN :minLength AND :maxLength';
        $params = ['field' => $field, 'prefix' => $prefix, 'minLength' => $minLength, 'maxLength' => $maxLength];
        if ($languageCode !== null) {
            $sql .= ' AND language_code = :languageCode';
            $params['languageCode'] = $languageCode;
        }

        return $this->getEntityManager()->getConnection()->fetchFirstColumn($sql, $params);
    }

    /**
     * For every language with an entry containing $token, that language's best such entry: an
     * entry that is exactly the token first (word field only), then by score.
     *
     * @return list<array{word: string, ipa: string, languageCode: string, score: int, pattern: string, wordLength: int}>
     */
    public function findBestEntryPerLanguage(int $field, string $token): array
    {
        $rows = $this->getEntityManager()->getConnection()->fetchAllAssociative(
            'SELECT word, ipa, language_code, score, pattern, word_length FROM (
                 SELECT ws.*, ROW_NUMBER() OVER (
                     PARTITION BY ws.language_code
                     ORDER BY ' . self::singleWordFirst($field) . 'ws.score DESC, ws.source_id DESC
                 ) AS rank_in_language
                 FROM word_search_token t
                 JOIN word_search ws ON ws.language_code = t.language_code AND ws.source_id = t.source_id
                 WHERE t.field = :field AND t.token = :token
             ) ranked
             WHERE rank_in_language = 1
             ORDER BY score DESC, language_code',
            ['field' => $field, 'token' => $token],
        );

        return array_map(self::toDocument(...), $rows);
    }

    /**
     * Entries containing any of $tokens, each entry once, ranked: contains $exactToken first, then
     * an entry that is exactly one of the tokens (word field only), then score, language, id.
     *
     * @param list<string> $tokens
     * @return list<array{word: string, ipa: string, languageCode: string, score: int, pattern: string, wordLength: int}>
     */
    public function findRankedEntriesByTokens(int $field, string $exactToken, array $tokens, ?string $languageCode, int $limit): array
    {
        if ($tokens === [] || $limit <= 0) {
            return [];
        }

        $languageFilter = $languageCode !== null ? ' AND t.language_code = :languageCode' : '';
        $single = $field === WordSearchTokenEntity::FIELD_WORD ? 'MAX(ws.word_length = t.token_length) DESC, ' : '';
        $params = ['field' => $field, 'exactToken' => $exactToken, 'tokens' => $tokens, 'limit' => $limit];
        $types = ['tokens' => ArrayParameterType::STRING, 'limit' => \PDO::PARAM_INT];
        if ($languageCode !== null) {
            $params['languageCode'] = $languageCode;
        }

        // Grouped by word_search's primary key, so its other columns are functionally dependent.
        $rows = $this->getEntityManager()->getConnection()->fetchAllAssociative(
            "SELECT ws.word, ws.ipa, ws.language_code, ws.score, ws.pattern, ws.word_length
             FROM word_search_token t
             JOIN word_search ws ON ws.language_code = t.language_code AND ws.source_id = t.source_id
             WHERE t.field = :field AND t.token IN (:tokens){$languageFilter}
             GROUP BY ws.language_code, ws.source_id
             ORDER BY MAX(t.token = :exactToken) DESC, {$single}ws.score DESC, ws.language_code, ws.source_id DESC
             LIMIT :limit",
            $params,
            $types,
        );

        return array_map(self::toDocument(...), $rows);
    }

    public function tokenExists(int $field, string $token, string $languageCode): bool
    {
        return $this->getEntityManager()->getConnection()->fetchOne(
            'SELECT 1 FROM word_search_token WHERE field = :field AND token = :token AND language_code = :languageCode LIMIT 1',
            ['field' => $field, 'token' => $token, 'languageCode' => $languageCode],
        ) !== false;
    }

    /**
     * Ranking term preferring an entry that consists of the token alone. Only meaningful for the
     * word field: a token is derived from the word, so it can only be as long as the word when it
     * is the whole word (lowercasing keeps the length).
     */
    private static function singleWordFirst(int $field): string
    {
        return $field === WordSearchTokenEntity::FIELD_WORD ? '(ws.word_length = t.token_length) DESC, ' : '';
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
