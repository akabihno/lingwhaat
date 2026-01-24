<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260124131012 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE wikipedia_canonical_pattern (id BIGINT AUTO_INCREMENT NOT NULL, article_id BIGINT NOT NULL, pattern LONGTEXT NOT NULL, pattern_hash BIGINT NOT NULL, script VARCHAR(64) NOT NULL, ts_created VARCHAR(255) NOT NULL, INDEX IDX_E3115C217294869C (article_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE wikipedia_canonical_pattern ADD CONSTRAINT FK_E3115C217294869C FOREIGN KEY (article_id) REFERENCES wikipedia_article (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wikipedia_article ADD processed INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE wikipedia_canonical_pattern DROP FOREIGN KEY FK_E3115C217294869C');
        $this->addSql('DROP TABLE wikipedia_canonical_pattern');
        $this->addSql('ALTER TABLE wikipedia_article DROP processed');
    }
}
