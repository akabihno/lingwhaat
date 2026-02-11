<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260123212851 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pronunciation_english_language CHANGE name name VARCHAR(256) NOT NULL, CHANGE ts_created ts_created VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX i_name ON pronunciation_estonian_language');
        $this->addSql('ALTER TABLE pronunciation_estonian_language CHANGE name name VARCHAR(256) NOT NULL, CHANGE ts_created ts_created VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX i_name ON pronunciation_french_language');
        $this->addSql('ALTER TABLE pronunciation_french_language CHANGE name name VARCHAR(256) NOT NULL, CHANGE ts_created ts_created VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX i_name ON pronunciation_galician_language');
        $this->addSql('ALTER TABLE pronunciation_galician_language CHANGE name name VARCHAR(256) NOT NULL, CHANGE ts_created ts_created VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX i_name ON pronunciation_georgian_language');
        $this->addSql('ALTER TABLE pronunciation_georgian_language CHANGE name name VARCHAR(256) NOT NULL, CHANGE ts_created ts_created VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX i_name ON pronunciation_german_language');
        $this->addSql('ALTER TABLE pronunciation_german_language CHANGE name name VARCHAR(256) NOT NULL, CHANGE ts_created ts_created VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX i_name ON pronunciation_greek_language');
        $this->addSql('ALTER TABLE pronunciation_greek_language CHANGE name name VARCHAR(256) NOT NULL, CHANGE ts_created ts_created VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX i_name ON pronunciation_gullah_language');
        $this->addSql('ALTER TABLE pronunciation_gullah_language CHANGE name name VARCHAR(256) NOT NULL, CHANGE ts_created ts_created VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX i_name ON pronunciation_hausa_language');
        $this->addSql('ALTER TABLE pronunciation_hausa_language CHANGE name name VARCHAR(256) NOT NULL, CHANGE ts_created ts_created VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX i_name ON pronunciation_hebrew_language');
        $this->addSql('ALTER TABLE pronunciation_hebrew_language CHANGE name name VARCHAR(256) NOT NULL, CHANGE ts_created ts_created VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX i_name ON pronunciation_hindi_language');
        $this->addSql('ALTER TABLE pronunciation_hindi_language CHANGE name name VARCHAR(256) NOT NULL, CHANGE ts_created ts_created VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX i_name ON pronunciation_hungarian_language');
        $this->addSql('ALTER TABLE pronunciation_hungarian_language CHANGE name name VARCHAR(256) NOT NULL, CHANGE ts_created ts_created VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX i_name ON pronunciation_icelandic_language');
        $this->addSql('ALTER TABLE pronunciation_icelandic_language CHANGE name name VARCHAR(256) NOT NULL, CHANGE ts_created ts_created VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX i_name ON pronunciation_italian_language');
        $this->addSql('ALTER TABLE pronunciation_italian_language CHANGE name name VARCHAR(256) NOT NULL, CHANGE ts_created ts_created VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX i_name ON pronunciation_japanese_language');
        $this->addSql('ALTER TABLE pronunciation_japanese_language CHANGE name name VARCHAR(256) NOT NULL, CHANGE ts_created ts_created VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX i_name ON pronunciation_kazakh_language');
        $this->addSql('ALTER TABLE pronunciation_kazakh_language CHANGE name name VARCHAR(256) NOT NULL, CHANGE ts_created ts_created VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX i_name ON pronunciation_komi_language');
        $this->addSql('ALTER TABLE pronunciation_komi_language CHANGE name name VARCHAR(256) NOT NULL, CHANGE ts_created ts_created VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX i_name ON pronunciation_latin_language');
        $this->addSql('ALTER TABLE pronunciation_latin_language CHANGE name name VARCHAR(256) NOT NULL, CHANGE ts_created ts_created VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX i_name ON pronunciation_latvian_language');
        $this->addSql('ALTER TABLE pronunciation_latvian_language CHANGE name name VARCHAR(256) NOT NULL, CHANGE ts_created ts_created VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX i_name ON pronunciation_lithuanian_language');
        $this->addSql('ALTER TABLE pronunciation_lithuanian_language CHANGE name name VARCHAR(256) NOT NULL, CHANGE ts_created ts_created VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX i_name ON pronunciation_mandarin_language');
        $this->addSql('ALTER TABLE pronunciation_mandarin_language CHANGE name name VARCHAR(256) NOT NULL, CHANGE ts_created ts_created VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX i_name ON pronunciation_middledutch_language');
        $this->addSql('ALTER TABLE pronunciation_middledutch_language CHANGE name name VARCHAR(256) NOT NULL, CHANGE ts_created ts_created VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX i_name ON pronunciation_mongolian_language');
        $this->addSql('ALTER TABLE pronunciation_mongolian_language CHANGE name name VARCHAR(256) NOT NULL, CHANGE ts_created ts_created VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX i_name ON pronunciation_norwegian_language');
        $this->addSql('ALTER TABLE pronunciation_norwegian_language CHANGE name name VARCHAR(256) NOT NULL, CHANGE ts_created ts_created VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX i_name ON pronunciation_olddutch_language');
        $this->addSql('ALTER TABLE pronunciation_olddutch_language CHANGE name name VARCHAR(256) NOT NULL, CHANGE ts_created ts_created VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX i_name ON pronunciation_pali_language');
        $this->addSql('ALTER TABLE pronunciation_pali_language CHANGE name name VARCHAR(256) NOT NULL, CHANGE ts_created ts_created VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX i_name ON pronunciation_polish_language');
        $this->addSql('ALTER TABLE pronunciation_polish_language CHANGE name name VARCHAR(256) NOT NULL, CHANGE ts_created ts_created VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX i_name ON pronunciation_portuguese_language');
        $this->addSql('ALTER TABLE pronunciation_portuguese_language CHANGE name name VARCHAR(256) NOT NULL, CHANGE ts_created ts_created VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX i_name ON pronunciation_romanian_language');
        $this->addSql('ALTER TABLE pronunciation_romanian_language CHANGE name name VARCHAR(256) NOT NULL, CHANGE ts_created ts_created VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX i_name ON pronunciation_russian_language');
        $this->addSql('ALTER TABLE pronunciation_russian_language CHANGE name name VARCHAR(256) NOT NULL, CHANGE ts_created ts_created VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX i_name ON pronunciation_serbocroatian_language');
        $this->addSql('ALTER TABLE pronunciation_serbocroatian_language CHANGE name name VARCHAR(256) NOT NULL, CHANGE ts_created ts_created VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX i_name ON pronunciation_somali_language');
        $this->addSql('ALTER TABLE pronunciation_somali_language CHANGE name name VARCHAR(256) NOT NULL, CHANGE ts_created ts_created VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX i_name ON pronunciation_spanish_language');
        $this->addSql('ALTER TABLE pronunciation_spanish_language CHANGE name name VARCHAR(256) NOT NULL, CHANGE ts_created ts_created VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX i_name ON pronunciation_swahili_language');
        $this->addSql('ALTER TABLE pronunciation_swahili_language CHANGE name name VARCHAR(256) NOT NULL, CHANGE ts_created ts_created VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX i_name ON pronunciation_swedish_language');
        $this->addSql('ALTER TABLE pronunciation_swedish_language CHANGE name name VARCHAR(256) NOT NULL, CHANGE ts_created ts_created VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX i_name ON pronunciation_tagalog_language');
        $this->addSql('ALTER TABLE pronunciation_tagalog_language CHANGE name name VARCHAR(256) NOT NULL, CHANGE ts_created ts_created VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX i_name ON pronunciation_turkish_language');
        $this->addSql('ALTER TABLE pronunciation_turkish_language CHANGE name name VARCHAR(256) NOT NULL, CHANGE ts_created ts_created VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX i_name ON pronunciation_ukrainian_language');
        $this->addSql('ALTER TABLE pronunciation_ukrainian_language CHANGE name name VARCHAR(256) NOT NULL, CHANGE ts_created ts_created VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX i_name ON pronunciation_urdu_language');
        $this->addSql('ALTER TABLE pronunciation_urdu_language CHANGE name name VARCHAR(256) NOT NULL, CHANGE ts_created ts_created VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX i_name ON pronunciation_uzbek_language');
        $this->addSql('ALTER TABLE pronunciation_uzbek_language CHANGE name name VARCHAR(256) NOT NULL, CHANGE ts_created ts_created VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX i_name ON pronunciation_vietnamese_language');
        $this->addSql('ALTER TABLE pronunciation_vietnamese_language CHANGE name name VARCHAR(256) NOT NULL, CHANGE ts_created ts_created VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX i_name ON pronunciation_wolof_language');
        $this->addSql('ALTER TABLE pronunciation_wolof_language CHANGE name name VARCHAR(256) NOT NULL, CHANGE ts_created ts_created VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pronunciation_english_language CHANGE name name VARCHAR(256) DEFAULT \'\', CHANGE ts_created ts_created DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE pronunciation_estonian_language CHANGE name name VARCHAR(256) DEFAULT \'\', CHANGE ts_created ts_created DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('CREATE INDEX i_name ON pronunciation_estonian_language (name)');
        $this->addSql('ALTER TABLE pronunciation_french_language CHANGE name name VARCHAR(256) DEFAULT \'\', CHANGE ts_created ts_created DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('CREATE INDEX i_name ON pronunciation_french_language (name)');
        $this->addSql('ALTER TABLE pronunciation_galician_language CHANGE name name VARCHAR(256) DEFAULT \'\', CHANGE ts_created ts_created DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('CREATE INDEX i_name ON pronunciation_galician_language (name)');
        $this->addSql('ALTER TABLE pronunciation_georgian_language CHANGE name name VARCHAR(256) DEFAULT \'\', CHANGE ts_created ts_created DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('CREATE INDEX i_name ON pronunciation_georgian_language (name)');
        $this->addSql('ALTER TABLE pronunciation_german_language CHANGE name name VARCHAR(256) DEFAULT \'\', CHANGE ts_created ts_created DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('CREATE INDEX i_name ON pronunciation_german_language (name)');
        $this->addSql('ALTER TABLE pronunciation_greek_language CHANGE name name VARCHAR(256) DEFAULT \'\', CHANGE ts_created ts_created DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('CREATE INDEX i_name ON pronunciation_greek_language (name)');
        $this->addSql('ALTER TABLE pronunciation_gullah_language CHANGE name name VARCHAR(256) DEFAULT \'\', CHANGE ts_created ts_created DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('CREATE INDEX i_name ON pronunciation_gullah_language (name)');
        $this->addSql('ALTER TABLE pronunciation_hausa_language CHANGE name name VARCHAR(256) DEFAULT \'\', CHANGE ts_created ts_created DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('CREATE INDEX i_name ON pronunciation_hausa_language (name)');
        $this->addSql('ALTER TABLE pronunciation_hebrew_language CHANGE name name VARCHAR(256) DEFAULT \'\', CHANGE ts_created ts_created DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('CREATE INDEX i_name ON pronunciation_hebrew_language (name)');
        $this->addSql('ALTER TABLE pronunciation_hindi_language CHANGE name name VARCHAR(256) DEFAULT \'\', CHANGE ts_created ts_created DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('CREATE INDEX i_name ON pronunciation_hindi_language (name)');
        $this->addSql('ALTER TABLE pronunciation_hungarian_language CHANGE name name VARCHAR(256) DEFAULT \'\', CHANGE ts_created ts_created DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('CREATE INDEX i_name ON pronunciation_hungarian_language (name)');
        $this->addSql('ALTER TABLE pronunciation_icelandic_language CHANGE name name VARCHAR(256) DEFAULT \'\', CHANGE ts_created ts_created DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('CREATE INDEX i_name ON pronunciation_icelandic_language (name)');
        $this->addSql('ALTER TABLE pronunciation_italian_language CHANGE name name VARCHAR(256) DEFAULT \'\', CHANGE ts_created ts_created DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('CREATE INDEX i_name ON pronunciation_italian_language (name)');
        $this->addSql('ALTER TABLE pronunciation_japanese_language CHANGE name name VARCHAR(256) DEFAULT \'\', CHANGE ts_created ts_created DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('CREATE INDEX i_name ON pronunciation_japanese_language (name)');
        $this->addSql('ALTER TABLE pronunciation_kazakh_language CHANGE name name VARCHAR(256) DEFAULT \'\', CHANGE ts_created ts_created DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('CREATE INDEX i_name ON pronunciation_kazakh_language (name)');
        $this->addSql('ALTER TABLE pronunciation_komi_language CHANGE name name VARCHAR(256) DEFAULT \'\', CHANGE ts_created ts_created DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('CREATE INDEX i_name ON pronunciation_komi_language (name)');
        $this->addSql('ALTER TABLE pronunciation_latin_language CHANGE name name VARCHAR(256) DEFAULT \'\', CHANGE ts_created ts_created DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('CREATE INDEX i_name ON pronunciation_latin_language (name)');
        $this->addSql('ALTER TABLE pronunciation_latvian_language CHANGE name name VARCHAR(256) DEFAULT \'\', CHANGE ts_created ts_created DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('CREATE INDEX i_name ON pronunciation_latvian_language (name)');
        $this->addSql('ALTER TABLE pronunciation_lithuanian_language CHANGE name name VARCHAR(256) DEFAULT \'\', CHANGE ts_created ts_created DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('CREATE INDEX i_name ON pronunciation_lithuanian_language (name)');
        $this->addSql('ALTER TABLE pronunciation_mandarin_language CHANGE name name VARCHAR(256) DEFAULT \'\', CHANGE ts_created ts_created DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('CREATE INDEX i_name ON pronunciation_mandarin_language (name)');
        $this->addSql('ALTER TABLE pronunciation_middledutch_language CHANGE name name VARCHAR(256) DEFAULT \'\', CHANGE ts_created ts_created DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('CREATE INDEX i_name ON pronunciation_middledutch_language (name)');
        $this->addSql('ALTER TABLE pronunciation_mongolian_language CHANGE name name VARCHAR(256) DEFAULT \'\', CHANGE ts_created ts_created DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('CREATE INDEX i_name ON pronunciation_mongolian_language (name)');
        $this->addSql('ALTER TABLE pronunciation_norwegian_language CHANGE name name VARCHAR(256) DEFAULT \'\', CHANGE ts_created ts_created DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('CREATE INDEX i_name ON pronunciation_norwegian_language (name)');
        $this->addSql('ALTER TABLE pronunciation_olddutch_language CHANGE name name VARCHAR(256) DEFAULT \'\', CHANGE ts_created ts_created DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('CREATE INDEX i_name ON pronunciation_olddutch_language (name)');
        $this->addSql('ALTER TABLE pronunciation_pali_language CHANGE name name VARCHAR(256) DEFAULT \'\', CHANGE ts_created ts_created DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('CREATE INDEX i_name ON pronunciation_pali_language (name)');
        $this->addSql('ALTER TABLE pronunciation_polish_language CHANGE name name VARCHAR(256) DEFAULT \'\', CHANGE ts_created ts_created DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('CREATE INDEX i_name ON pronunciation_polish_language (name)');
        $this->addSql('ALTER TABLE pronunciation_portuguese_language CHANGE name name VARCHAR(256) DEFAULT \'\', CHANGE ts_created ts_created DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('CREATE INDEX i_name ON pronunciation_portuguese_language (name)');
        $this->addSql('ALTER TABLE pronunciation_romanian_language CHANGE name name VARCHAR(256) DEFAULT \'\', CHANGE ts_created ts_created DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('CREATE INDEX i_name ON pronunciation_romanian_language (name)');
        $this->addSql('ALTER TABLE pronunciation_russian_language CHANGE name name VARCHAR(256) DEFAULT \'\', CHANGE ts_created ts_created DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('CREATE INDEX i_name ON pronunciation_russian_language (name)');
        $this->addSql('ALTER TABLE pronunciation_serbocroatian_language CHANGE name name VARCHAR(256) DEFAULT \'\', CHANGE ts_created ts_created DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('CREATE INDEX i_name ON pronunciation_serbocroatian_language (name)');
        $this->addSql('ALTER TABLE pronunciation_somali_language CHANGE name name VARCHAR(256) DEFAULT \'\', CHANGE ts_created ts_created DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('CREATE INDEX i_name ON pronunciation_somali_language (name)');
        $this->addSql('ALTER TABLE pronunciation_spanish_language CHANGE name name VARCHAR(256) DEFAULT \'\', CHANGE ts_created ts_created DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('CREATE INDEX i_name ON pronunciation_spanish_language (name)');
        $this->addSql('ALTER TABLE pronunciation_swahili_language CHANGE name name VARCHAR(256) DEFAULT \'\', CHANGE ts_created ts_created DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('CREATE INDEX i_name ON pronunciation_swahili_language (name)');
        $this->addSql('ALTER TABLE pronunciation_swedish_language CHANGE name name VARCHAR(256) DEFAULT \'\', CHANGE ts_created ts_created DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('CREATE INDEX i_name ON pronunciation_swedish_language (name)');
        $this->addSql('ALTER TABLE pronunciation_tagalog_language CHANGE name name VARCHAR(256) DEFAULT \'\', CHANGE ts_created ts_created DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('CREATE INDEX i_name ON pronunciation_tagalog_language (name)');
        $this->addSql('ALTER TABLE pronunciation_turkish_language CHANGE name name VARCHAR(256) DEFAULT \'\', CHANGE ts_created ts_created DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('CREATE INDEX i_name ON pronunciation_turkish_language (name)');
        $this->addSql('ALTER TABLE pronunciation_ukrainian_language CHANGE name name VARCHAR(256) DEFAULT \'\', CHANGE ts_created ts_created DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('CREATE INDEX i_name ON pronunciation_ukrainian_language (name)');
        $this->addSql('ALTER TABLE pronunciation_urdu_language CHANGE name name VARCHAR(256) DEFAULT \'\', CHANGE ts_created ts_created DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('CREATE INDEX i_name ON pronunciation_urdu_language (name)');
        $this->addSql('ALTER TABLE pronunciation_uzbek_language CHANGE name name VARCHAR(256) DEFAULT \'\', CHANGE ts_created ts_created DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('CREATE INDEX i_name ON pronunciation_uzbek_language (name)');
        $this->addSql('ALTER TABLE pronunciation_vietnamese_language CHANGE name name VARCHAR(256) DEFAULT \'\', CHANGE ts_created ts_created DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('CREATE INDEX i_name ON pronunciation_vietnamese_language (name)');
        $this->addSql('ALTER TABLE pronunciation_wolof_language CHANGE name name VARCHAR(256) DEFAULT \'\', CHANGE ts_created ts_created DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('CREATE INDEX i_name ON pronunciation_wolof_language (name)');
    }
}
