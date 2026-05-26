<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260430120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Reshape manuscript_alphabet_decode_result for canonical-pattern candidates and OpenAI selection';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('TRUNCATE TABLE manuscript_alphabet_decode_result');

        $this->addSql('DROP INDEX idx_decode_unscored ON manuscript_alphabet_decode_result');
        $this->addSql('ALTER TABLE manuscript_alphabet_decode_result DROP COLUMN decoded_phrase');
        $this->addSql('ALTER TABLE manuscript_alphabet_decode_result DROP COLUMN word_matches');
        $this->addSql('ALTER TABLE manuscript_alphabet_decode_result DROP COLUMN language_score');
        $this->addSql('ALTER TABLE manuscript_alphabet_decode_result DROP COLUMN scored_language_code');

        $this->addSql('ALTER TABLE manuscript_alphabet_decode_result ADD cipher_words TEXT NOT NULL');
        $this->addSql('ALTER TABLE manuscript_alphabet_decode_result ADD word_candidates LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE manuscript_alphabet_decode_result ADD selected_phrase TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE manuscript_alphabet_decode_result ADD openai_status VARCHAR(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE manuscript_alphabet_decode_result ADD priority_hint DOUBLE PRECISION NOT NULL DEFAULT 0');

        $this->addSql('CREATE INDEX idx_decode_processing ON manuscript_alphabet_decode_result (openai_status, priority_hint)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_decode_processing ON manuscript_alphabet_decode_result');

        $this->addSql('ALTER TABLE manuscript_alphabet_decode_result DROP COLUMN cipher_words');
        $this->addSql('ALTER TABLE manuscript_alphabet_decode_result DROP COLUMN word_candidates');
        $this->addSql('ALTER TABLE manuscript_alphabet_decode_result DROP COLUMN selected_phrase');
        $this->addSql('ALTER TABLE manuscript_alphabet_decode_result DROP COLUMN openai_status');
        $this->addSql('ALTER TABLE manuscript_alphabet_decode_result DROP COLUMN priority_hint');

        $this->addSql('ALTER TABLE manuscript_alphabet_decode_result ADD decoded_phrase LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE manuscript_alphabet_decode_result ADD word_matches LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE manuscript_alphabet_decode_result ADD language_score DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE manuscript_alphabet_decode_result ADD scored_language_code VARCHAR(8) DEFAULT NULL');

        $this->addSql('CREATE INDEX idx_decode_unscored ON manuscript_alphabet_decode_result (language_score)');
    }
}
