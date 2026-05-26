<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260425120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create manuscript_alphabet_decode_result table for alphabet substitution decode results';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE manuscript_alphabet_decode_result (
                id            INT          NOT NULL AUTO_INCREMENT,
                match_id      INT          NOT NULL,
                language_code VARCHAR(8)   NOT NULL,
                window_position INT        NOT NULL,
                word_lengths  VARCHAR(64)  NOT NULL,
                decoded_phrase LONGTEXT    NOT NULL,
                word_matches  LONGTEXT     DEFAULT NULL,
                language_score DOUBLE PRECISION DEFAULT NULL,
                scored_language_code VARCHAR(8) DEFAULT NULL,
                ts_created    VARCHAR(255) NOT NULL,
                PRIMARY KEY (id),
                INDEX idx_decode_match_id (match_id),
                INDEX idx_decode_language (language_code),
                INDEX idx_decode_unscored (language_score)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE manuscript_alphabet_decode_result');
    }
}
