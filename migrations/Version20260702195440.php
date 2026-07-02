<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260702195440 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE manuscript_alphabet_decode_result CHANGE language_code language_code VARCHAR(16) NOT NULL');
        $this->addSql('ALTER TABLE manuscript_pattern_match_result CHANGE language_code language_code VARCHAR(16) DEFAULT NULL, CHANGE language_code_atbash language_code_atbash VARCHAR(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE wikipedia_article CHANGE language_code language_code VARCHAR(16) NOT NULL');
        $this->addSql('ALTER TABLE wikipedia_pattern_index_offset CHANGE language_code language_code VARCHAR(16) NOT NULL, CHANGE last_run_at last_run_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE wikipedia_pattern_parse_schedule CHANGE language_code language_code VARCHAR(16) NOT NULL');
        $this->addSql('ALTER TABLE words_popularity_score_set_offset CHANGE language_code language_code VARCHAR(16) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE manuscript_alphabet_decode_result CHANGE language_code language_code VARCHAR(8) NOT NULL');
        $this->addSql('ALTER TABLE manuscript_pattern_match_result CHANGE language_code language_code VARCHAR(8) DEFAULT NULL, CHANGE language_code_atbash language_code_atbash VARCHAR(8) DEFAULT NULL');
        $this->addSql('ALTER TABLE wikipedia_article CHANGE language_code language_code VARCHAR(8) NOT NULL');
        $this->addSql('ALTER TABLE wikipedia_pattern_index_offset CHANGE language_code language_code VARCHAR(8) NOT NULL, CHANGE last_run_at last_run_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE wikipedia_pattern_parse_schedule CHANGE language_code language_code VARCHAR(8) NOT NULL');
        $this->addSql('ALTER TABLE words_popularity_score_set_offset CHANGE language_code language_code VARCHAR(8) NOT NULL');
    }
}
