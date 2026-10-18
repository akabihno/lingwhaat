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

    /**
     * Entries with a token matching a SQL LIKE pattern (translated from an Elasticsearch wildcard
     * by the caller), each entry once, most popular first.
     *
     * @return list<array{word: string, ipa: string, languageCode: string, score: int, pattern: string, wordLength: int}>
     */
    public function findByTokenLike(int $field, string $likePattern, ?int $tokenLength, ?string $languageCode, int $limit): array
    {
        $sql = 'SELECT ws.word, ws.ipa, ws.language_code, ws.score, ws.pattern, ws.word_length
                FROM word_search_token t
                JOIN word_search ws ON ws.language_code = t.language_code AND ws.source_id = t.source_id
                WHERE t.field = :field AND t.token LIKE :likePattern';
        $params = ['field' => $field, 'likePattern' => $likePattern, 'limit' => $limit];
        if ($tokenLength !== null) {
            $sql .= ' AND t.token_length = :tokenLength';
            $params['tokenLength'] = $tokenLength;
        }
        if ($languageCode !== null) {
            $sql .= ' AND t.language_code = :languageCode';
            $params['languageCode'] = $languageCode;
        }
        // Grouped by word_search's primary key, so its other columns are functionally dependent.
        $sql .= ' GROUP BY ws.language_code, ws.source_id ORDER BY ws.score DESC, ws.language_code, ws.source_id DESC LIMIT :limit';

        $rows = $this->getEntityManager()->getConnection()->fetchAllAssociative($sql, $params, ['limit' => \PDO::PARAM_INT]);

        return array_map(self::toDocument(...), $rows);
    }

    /**
     * Entries matching positional constraints on the lowercased word, exactly as the former
     * Elasticsearch painless script checked them (see PatternSearchService::buildParameterizedScript,
     * whose params this takes): optional exact length, minimum length maxPosition, every group of
     * 0-based positions holding the same character, and fixed characters (first character of each
     * given string) at 0-based positions. Most popular first; a single-row query (an existence
     * check) skips the ordering.
     *
     * @param array{exactLength: ?int, maxPosition: int, samePositions: list<list<int>>, fixedChars: list<array{pos: int, char: string}>} $params
     * @param list<string>|null $notLanguageCodes
     * @return list<array{word: string, ipa: string, languageCode: string, score: int, pattern: string, wordLength: int}>
     */
    public function findByPositions(array $params, ?string $languageCode, ?array $notLanguageCodes, int $limit): array
    {
        $conditions = [];
        $values = ['limit' => $limit];
        $types = ['limit' => \PDO::PARAM_INT];
        $lower = 'LOWER(ws.word)';

        if ($languageCode !== null) {
            $conditions[] = 'ws.language_code = :languageCode';
            $values['languageCode'] = $languageCode;
        }
        if (!empty($notLanguageCodes)) {
            $conditions[] = 'ws.language_code NOT IN (:notLanguageCodes)';
            $values['notLanguageCodes'] = array_values($notLanguageCodes);
            $types['notLanguageCodes'] = ArrayParameterType::STRING;
        }
        if ($params['exactLength'] !== null) {
            // word_length (as stored) narrows the scan; lowercasing can only lengthen a word.
            $conditions[] = "ws.word_length BETWEEN :exactLengthMin AND :exactLength AND CHAR_LENGTH({$lower}) = :exactLength";
            $values['exactLength'] = $params['exactLength'];
            $values['exactLengthMin'] = max(0, $params['exactLength'] - 2);
        }
        if ($params['maxPosition'] > 0) {
            $conditions[] = "CHAR_LENGTH({$lower}) >= :maxPosition";
            $values['maxPosition'] = $params['maxPosition'];
        }
        foreach ($params['samePositions'] as $group) {
            $first = (int) $group[0] + 1;
            foreach (array_slice($group, 1) as $position) {
                $conditions[] = sprintf('SUBSTRING(%s, %d, 1) = SUBSTRING(%s, %d, 1)', $lower, $first, $lower, (int) $position + 1);
            }
        }
        foreach ($params['fixedChars'] as $i => $constraint) {
            $conditions[] = sprintf('SUBSTRING(%s, %d, 1) = :fixedChar%d', $lower, (int) $constraint['pos'] + 1, $i);
            $values["fixedChar{$i}"] = mb_substr((string) $constraint['char'], 0, 1);
        }

        $sql = 'SELECT ws.word, ws.ipa, ws.language_code, ws.score, ws.pattern, ws.word_length FROM word_search ws'
            . ($conditions !== [] ? ' WHERE ' . implode(' AND ', $conditions) : '')
            . ($limit > 1 ? ' ORDER BY ws.score DESC, ws.language_code, ws.source_id DESC' : '')
            . ' LIMIT :limit';

        $rows = $this->getEntityManager()->getConnection()->fetchAllAssociative($sql, $values, $types);

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
