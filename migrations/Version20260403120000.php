<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260403120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create word_category table for semantic category vectors';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE word_category (
                id          INT AUTO_INCREMENT NOT NULL,
                language_code VARCHAR(10)  NOT NULL,
                word        VARCHAR(255)     NOT NULL,
                categories  JSON             NOT NULL,
                ts_created  DATETIME         NOT NULL,
                ts_updated  DATETIME         NOT NULL,
                PRIMARY KEY (id),
                UNIQUE INDEX uq_language_word (language_code, word),
                INDEX idx_wc_language_code (language_code)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE word_category');
    }
}
