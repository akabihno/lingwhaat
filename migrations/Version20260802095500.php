<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Lookup index for the Wikipedia ingest duplicate check: the parser draws random titles, so it
 * must ask whether a link is already stored before spending a fetch on it.
 *
 * Prefix index on wikipedia_link (VARCHAR(2048) — far past InnoDB's 3072-byte key limit at
 * utf8mb4). Deliberately NOT unique: the table already contains duplicates from before the check
 * existed, so a UNIQUE index would fail to build. It can be tightened to UNIQUE later, after a
 * dedupe pass.
 */
final class Version20260802095500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add i_lang_link (language_code, wikipedia_link(191)) to wikipedia_article for the ingest duplicate check';
    }

    public function up(Schema $schema): void
    {
        // INPLACE/LOCK=NONE: wikipedia_article holds millions of rows and the ingest workers write
        // to it continuously, so the build must not block concurrent DML.
        $this->addSql('ALTER TABLE wikipedia_article ADD INDEX i_lang_link (language_code, wikipedia_link(191)), ALGORITHM=INPLACE, LOCK=NONE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX i_lang_link ON wikipedia_article');
    }
}
