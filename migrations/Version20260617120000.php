<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260617120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store words popularity offset per language in its own table; drop the unused schedule table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE words_popularity_score_set_offset (id INT AUTO_INCREMENT NOT NULL, language_code VARCHAR(8) NOT NULL, offset BIGINT NOT NULL DEFAULT 0, UNIQUE INDEX uq_words_popularity_offset_language_code (language_code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('DROP TABLE words_popularity_score_set_schedule');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE words_popularity_score_set_schedule (id INT AUTO_INCREMENT NOT NULL, language_code VARCHAR(8) NOT NULL, offset INT DEFAULT 0 NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('DROP TABLE words_popularity_score_set_offset');
    }
}
