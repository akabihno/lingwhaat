<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Tables that replace the Elasticsearch words_index (ES phase 2): word_search holds every
 * dictionary entry of every language, word_search_token its search tokens. Both are filled by
 * `app:word-search-rebuild` from the pronunciation_<language>_language tables.
 *
 * The web user needs DML on both before the rebuild can run; as root:
 *   GRANT SELECT,INSERT,UPDATE,DELETE ON lingwhaat.word_search TO 'web'@'%';
 *   GRANT SELECT,INSERT,UPDATE,DELETE ON lingwhaat.word_search_token TO 'web'@'%';
 * (also recorded in imports/create_web_user.sql).
 */
final class Version20261018140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create word_search and word_search_token (MySQL replacement for the words_index ES index)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE word_search (language_code VARCHAR(16) NOT NULL, source_id INT NOT NULL, word VARCHAR(256) NOT NULL, ipa VARCHAR(255) NOT NULL, pattern VARCHAR(1100) NOT NULL, pattern_crc INT UNSIGNED NOT NULL, word_length SMALLINT UNSIGNED NOT NULL, score INT DEFAULT 0 NOT NULL, INDEX idx_ws_pattern (language_code, pattern_crc, score), INDEX idx_ws_score (language_code, score), INDEX idx_ws_length (language_code, word_length), PRIMARY KEY(language_code, source_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_bin` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE word_search_token (field SMALLINT UNSIGNED NOT NULL, token VARCHAR(255) NOT NULL, language_code VARCHAR(16) NOT NULL, source_id INT NOT NULL, token_prefix VARCHAR(1) NOT NULL, token_length SMALLINT UNSIGNED NOT NULL, INDEX idx_wst_bucket (field, token_prefix, token_length), INDEX idx_wst_lang_bucket (field, language_code, token_prefix, token_length), PRIMARY KEY(field, token, language_code, source_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_bin` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE word_search_token');
        $this->addSql('DROP TABLE word_search');
    }
}
