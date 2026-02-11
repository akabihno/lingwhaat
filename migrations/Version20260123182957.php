<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260123182957 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add score column (default 0, not null) and shrink ipa to VARCHAR(255) with truncation for pronunciation_*_language tables';
    }

    public function up(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();

        foreach ($sm->listTables() as $table) {
            $tableName = $table->getName();

            if (!preg_match('/^pronunciation_.+_language$/', $tableName)) {
                continue;
            }

            // Re-fetch columns from schema manager to be safe (some DBAL versions cache table objects).
            $columns = $sm->introspectTable($tableName)->getColumns();

            // 1) Truncate long IPA strings so shrinking won't fail in strict mode
            if (isset($columns['ipa'])) {
                $this->addSql(sprintf(
                    "UPDATE `%s` SET `ipa` = LEFT(`ipa`, 255) WHERE CHAR_LENGTH(`ipa`) > 255",
                    $tableName
                ));

                // 2) Shrink column
                $this->addSql(sprintf(
                    "ALTER TABLE `%s` MODIFY `ipa` VARCHAR(255) NOT NULL",
                    $tableName
                ));
            }

            // 3) Ensure score exists (not null, default 0)
            if (!isset($columns['score'])) {
                $this->addSql(sprintf(
                    "ALTER TABLE `%s` ADD `score` INT NOT NULL DEFAULT 0",
                    $tableName
                ));
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(true, 'Irreversible migration: IPA values may have been truncated to 255 chars.');
    }
}