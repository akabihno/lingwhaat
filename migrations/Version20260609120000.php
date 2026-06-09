<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260609120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Composite (language_code, id) index for keyset paging; convert pattern-index offset cursor to last_article_id';
    }

    public function up(Schema $schema): void
    {
        // Composite index supports both the per-language COUNT(*) and the keyset
        // "WHERE language_code = ? AND id > ? ORDER BY id" seek paging in a single covering range.
        // Created first so the conversion UPDATE below can satisfy its per-language ORDER BY id from
        // the index, and so language_code lookups are never left unindexed.
        $this->addSql('CREATE INDEX i_lang_id ON wikipedia_article (language_code, id)');
        $this->addSql('DROP INDEX i_language_code ON wikipedia_article');

        // Rename the cursor column, preserving the existing row-offset values for now. The next
        // statement reinterprets them: a value of N meant "the first N articles (id ASC) are done",
        // so the equivalent keyset cursor is the id of the N-th article for that language.
        $this->addSql('ALTER TABLE wikipedia_pattern_index_offset CHANGE current_offset last_article_id BIGINT NOT NULL DEFAULT 0');

        // Convert each row offset to the matching article id losslessly: number the articles per
        // language by id and pick the one whose position equals the stored offset N. Rows still at 0
        // (never indexed) match nothing and stay 0. If an offset ever exceeds a language's article
        // count it also matches nothing and stays at its raw value — but the handler resets any
        // short batch to 0 on its next run, so it self-heals into a clean full pass.
        $this->addSql(<<<'SQL'
            UPDATE wikipedia_pattern_index_offset o
            JOIN (
                SELECT
                    language_code,
                    id,
                    ROW_NUMBER() OVER (PARTITION BY language_code ORDER BY id ASC) AS rn
                FROM wikipedia_article
            ) ranked
                ON ranked.language_code = o.language_code
               AND ranked.rn = o.last_article_id
            SET o.last_article_id = ranked.id
            WHERE o.last_article_id > 0
            SQL);
    }

    public function down(Schema $schema): void
    {
        // Reverse the mapping while the column is still bigint to avoid truncation: the row offset is
        // simply how many articles have an id at or below the stored cursor id.
        $this->addSql(<<<'SQL'
            UPDATE wikipedia_pattern_index_offset o
            SET o.last_article_id = (
                SELECT COUNT(*)
                FROM wikipedia_article a
                WHERE a.language_code = o.language_code
                  AND a.id <= o.last_article_id
            )
            WHERE o.last_article_id > 0
            SQL);

        $this->addSql('ALTER TABLE wikipedia_pattern_index_offset CHANGE last_article_id current_offset INT NOT NULL DEFAULT 0');

        $this->addSql('CREATE INDEX i_language_code ON wikipedia_article (language_code)');
        $this->addSql('DROP INDEX i_lang_id ON wikipedia_article');
    }
}
