<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260411120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add source_id, language_code, language_score to manuscript_pattern_match_result';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE manuscript_pattern_match_result
                ADD source_id INT NOT NULL AFTER match_id,
                ADD language_code VARCHAR(8) NULL DEFAULT NULL AFTER results,
                ADD language_score FLOAT NULL DEFAULT NULL AFTER language_code,
                ADD INDEX idx_source_id (source_id),
                ADD INDEX idx_unscored (language_score)
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE manuscript_pattern_match_result
                DROP INDEX idx_source_id,
                DROP INDEX idx_unscored,
                DROP COLUMN source_id,
                DROP COLUMN language_code,
                DROP COLUMN language_score
        SQL);
    }
}
