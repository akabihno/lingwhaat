<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Character-grouping table for the manuscript pattern search: rows declare runs of manuscript
 * characters that must be treated as a single glyph (keyed by source_id, any number per source,
 * not language-specific).
 */
final class Version20260705160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create manuscript_character_group (per-source glyph groupings for pattern search)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE manuscript_character_group (id INT AUTO_INCREMENT NOT NULL, source_id INT NOT NULL, sequence VARCHAR(255) NOT NULL, INDEX idx_mcg_source_id (source_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE manuscript_character_group');
    }
}
