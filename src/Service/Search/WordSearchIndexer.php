<?php

namespace App\Service\Search;

use App\Constant\LanguageMappings;
use App\Entity\WordSearchTokenEntity;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Rebuilds word_search / word_search_token (the MySQL replacement for the words_index ES index)
 * from the per-language pronunciation tables.
 *
 * Each language is rebuilt in one transaction (delete its rows, re-insert from the source), so
 * readers see either the old or the new copy of a language, never a half-built one.
 */
class WordSearchIndexer
{
    private const int SOURCE_BATCH_SIZE = 5000;
    private const int ENTRY_INSERT_BATCH_SIZE = 1000;
    private const int TOKEN_INSERT_BATCH_SIZE = 2000;
    // Lucene's StandardTokenizer splits tokens longer than this; it is also the column length.
    private const int MAX_TOKEN_LENGTH = 255;
    private const string IPA_NOT_AVAILABLE = 'Not available';

    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * Language code => source table for every language entity.
     *
     * @return array<string, string>
     */
    public function getLanguageTables(): array
    {
        $tables = [];
        foreach ($this->em->getMetadataFactory()->getAllMetadata() as $metadata) {
            $entityClass = $metadata->getName();
            if (!str_ends_with($entityClass, LanguageMappings::LANGUAGE_ENTITY)) {
                continue;
            }

            $languageCode = LanguageMappings::detectLanguageCodeFromEntity(new $entityClass());
            if ($languageCode === null || $languageCode === '') {
                continue;
            }
            // Two tables under one code would overwrite each other's rows (same source ids).
            if (isset($tables[$languageCode])) {
                throw new \LogicException(sprintf('Language code "%s" maps to both %s and %s.', $languageCode, $tables[$languageCode], $metadata->getTableName()));
            }
            $tables[$languageCode] = $metadata->getTableName();
        }
        ksort($tables);

        return $tables;
    }

    /**
     * @return array{entries: int, tokens: int}
     */
    public function rebuildLanguage(string $languageCode, string $sourceTable): array
    {
        $connection = $this->em->getConnection();

        return $connection->transactional(function (Connection $connection) use ($languageCode, $sourceTable): array {
            $connection->executeStatement('DELETE FROM word_search_token WHERE field IN (?, ?) AND language_code = ?', [
                WordSearchTokenEntity::FIELD_WORD, WordSearchTokenEntity::FIELD_IPA, $languageCode,
            ]);
            $connection->executeStatement('DELETE FROM word_search WHERE language_code = ?', [$languageCode]);

            $entries = 0;
            $tokens = 0;
            $afterId = 0;
            do {
                // Keyset pagination: OFFSET would rescan the table on every page.
                $rows = $connection->fetchAllAssociative(
                    "SELECT id, name, ipa, score FROM {$sourceTable} WHERE id > ? ORDER BY id LIMIT " . self::SOURCE_BATCH_SIZE,
                    [$afterId],
                );
                if ($rows === []) {
                    break;
                }
                $afterId = (int) $rows[array_key_last($rows)]['id'];

                $entryRows = [];
                $tokenRows = [];
                foreach ($rows as $row) {
                    $sourceId = (int) $row['id'];
                    $word = (string) $row['name'];
                    $ipa = (string) $row['ipa'];
                    $pattern = CanonicalPattern::fromString(mb_strtolower($word));

                    $entryRows[] = [$languageCode, $sourceId, $word, $ipa, $pattern, crc32($pattern), mb_strlen($word), (int) $row['score']];

                    foreach ($this->tokensOf($word) as $token) {
                        $tokenRows[] = $this->tokenRow(WordSearchTokenEntity::FIELD_WORD, $token, $languageCode, $sourceId);
                    }
                    if (!$this->isIpaPlaceholder($ipa, $word)) {
                        foreach ($this->tokensOf($ipa) as $token) {
                            $tokenRows[] = $this->tokenRow(WordSearchTokenEntity::FIELD_IPA, $token, $languageCode, $sourceId);
                        }
                    }
                }

                foreach (array_chunk($entryRows, self::ENTRY_INSERT_BATCH_SIZE) as $chunk) {
                    $this->insert($connection, 'word_search', ['language_code', 'source_id', 'word', 'ipa', 'pattern', 'pattern_crc', 'word_length', 'score'], $chunk);
                }
                foreach (array_chunk($tokenRows, self::TOKEN_INSERT_BATCH_SIZE) as $chunk) {
                    $this->insert($connection, 'word_search_token', ['field', 'token', 'language_code', 'source_id', 'token_prefix', 'token_length'], $chunk);
                }

                $entries += count($entryRows);
                $tokens += count($tokenRows);
            } while (count($rows) === self::SOURCE_BATCH_SIZE);

            return ['entries' => $entries, 'tokens' => $tokens];
        });
    }

    /**
     * Distinct tokens of a value; a token is matched at most once per entry, as an ES term was.
     *
     * @return list<string>
     */
    private function tokensOf(string $value): array
    {
        $tokens = [];
        foreach (WordTokenizer::tokenize($value) as $token) {
            // Lucene splits over-long tokens rather than dropping them.
            foreach (mb_str_split($token, self::MAX_TOKEN_LENGTH) as $piece) {
                $tokens[$piece] = true;
            }
        }

        return array_keys($tokens);
    }

    /**
     * @return list<int|string>
     */
    private function tokenRow(int $field, string $token, string $languageCode, int $sourceId): array
    {
        return [$field, $token, $languageCode, $sourceId, mb_substr($token, 0, 1), mb_strlen($token)];
    }

    /**
     * IPA values that are not pronunciations: the parser's "Not available" marker, and the word
     * itself copied into the IPA column (with a leading no-break space, ~670k Dutch entries).
     */
    private function isIpaPlaceholder(string $ipa, string $word): bool
    {
        if ($ipa === self::IPA_NOT_AVAILABLE) {
            return true;
        }

        $trimmed = preg_replace('/^[\s\x{00A0}]+|[\s\x{00A0}]+$/u', '', $ipa) ?? $ipa;

        return $trimmed === '' || $trimmed === $word;
    }

    /**
     * @param list<string> $columns
     * @param list<list<int|string>> $rows
     */
    private function insert(Connection $connection, string $table, array $columns, array $rows): void
    {
        $placeholders = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
        $connection->executeStatement(
            sprintf('INSERT INTO %s (%s) VALUES %s', $table, implode(', ', $columns), implode(', ', array_fill(0, count($rows), $placeholders))),
            array_merge(...$rows),
        );
    }
}
