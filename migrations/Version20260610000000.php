<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260610000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add last_run_at and next_article_limit to wikipedia_pattern_index_offset for self-chaining dispatch';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE wikipedia_pattern_index_offset ADD last_run_at DATETIME NULL');
        $this->addSql('ALTER TABLE wikipedia_pattern_index_offset ADD next_article_limit INT NOT NULL DEFAULT 5');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE wikipedia_pattern_index_offset DROP COLUMN last_run_at');
        $this->addSql('ALTER TABLE wikipedia_pattern_index_offset DROP COLUMN next_article_limit');
    }
}
