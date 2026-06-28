<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260623120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add language_code_atbash and language_score_atbash columns to manuscript_pattern_match_result for the Atbash language scorer';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE manuscript_pattern_match_result ADD language_code_atbash VARCHAR(8) DEFAULT NULL, ADD language_score_atbash DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE manuscript_pattern_match_result DROP language_code_atbash, DROP language_score_atbash');
    }
}
