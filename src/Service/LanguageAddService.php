<?php

namespace App\Service;

use App\Query\NewLanguageQuery;
use RuntimeException;

class LanguageAddService
{
    private string $sqlFilePath;

    public function __construct(protected NewLanguageQuery $languageQuery)
    {
        $this->sqlFilePath = dirname(__DIR__, 2) . '/imports/create_web_user.sql';
    }

    public function addLanguage($language): void
    {
        $this->languageQuery->addLanguage($language);
        $this->addGrantStatements($language);
    }

    private function addGrantStatements(string $language): void
    {
        if (!file_exists($this->sqlFilePath)) {
            throw new RuntimeException("SQL file not found: {$this->sqlFilePath}");
        }

        $content = file_get_contents($this->sqlFilePath);
        $pronunciationTable = "pronunciation_{$language}_language";

        if (str_contains($content, $pronunciationTable)) {
            return;
        }

        $flushPos = strpos($content, 'FLUSH PRIVILEGES;');

        if ($flushPos === false) {
            throw new RuntimeException("Could not find 'FLUSH PRIVILEGES;' in SQL file");
        }

        $newGrants = "GRANT SELECT,INSERT,UPDATE ON lingwhaat.{$pronunciationTable} TO '\${MYSQL_WEB_USER}'@'%';\n";
        $updatedContent = substr_replace($content, $newGrants, $flushPos, 0);

        file_put_contents($this->sqlFilePath, $updatedContent);
    }

}