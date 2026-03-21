<?php

namespace App\Query;

use Dotenv\Dotenv;
use PDO;
use PDOException;

class AbstractQuery
{
    const int PROCESSING_LIMIT = 40;
    protected PDO $pdo;
    public function connect(): void
    {
        if (isset($this->pdo)) {
            return;
        }

        Dotenv::createImmutable('/var/www/html/')->load();

        $dbHost = $_ENV['DB_HOST'];
        $dbPort = $_ENV['MYSQL_PORT'];
        $dbName = $_ENV['MYSQL_DATABASE'];
        $dbPassword = $_ENV['MYSQL_ROOT_PASSWORD'];

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $dbHost, $dbPort, $dbName);
        $username = 'root';

        try {
            $this->pdo = new PDO(
                $dsn,
                $username,
                $dbPassword,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_PERSISTENT => true,
                ]
            );
        } catch (PDOException $e) {
            echo 'Connection failed: ' . $e->getMessage();
        }
    }

    public function fetch($sql): array
    {
        $result = [];
        $stmt = $this->pdo->query($sql);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[] = $row;
        }

        return $result;
    }

    public function bulkUpdateIpa(string $language, array $updates): void
    {
        if (empty($updates)) {
            return;
        }

        $this->connect();

        $caseStatements = [];
        $names = [];
        $params = [];

        foreach ($updates as $index => $update) {
            $nameParam = ":name{$index}";
            $ipaParam = ":ipa{$index}";

            $caseStatements[] = "WHEN name = {$nameParam} THEN {$ipaParam}";
            $names[] = $nameParam;

            $params[$nameParam] = $update['name'];
            $params[$ipaParam] = $update['ipa'];
        }

        $caseClause = implode(' ', $caseStatements);
        $namesClause = implode(', ', $names);

        $query = "UPDATE {$this->getBaseTable($language)}
                  SET ipa = CASE {$caseClause} END,
                      ts_created = NOW()
                  WHERE name IN ({$namesClause})";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
    }

    public function bulkInsertLinks(string $language, array $links): void
    {
        if (empty($links)) {
            return;
        }

        $this->connect();

        $valuePlaceholders = [];
        $params = [];

        foreach ($links as $index => $link) {
            $nameParam = ":name{$index}";
            $linkParam = ":link{$index}";

            $valuePlaceholders[] = "({$nameParam}, {$linkParam})";

            $params[$nameParam] = $link['name'];
            $params[$linkParam] = $link['link'];
        }

        $valuesClause = implode(', ', $valuePlaceholders);

        $query = "INSERT INTO {$this->getLinksTable($language)} (name, link) VALUES {$valuesClause}";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
    }

    public function bulkInsertNames(string $language, array $names, int $chunkSize = 500): void
    {
        if (empty($names)) {
            return;
        }

        $this->connect();

        $names = array_values(array_filter(array_map(
            static fn ($n) => is_string($n) ? trim($n) : '',
            $names
        )));

        if (empty($names)) {
            return;
        }

        foreach (array_chunk($names, $chunkSize) as $chunk) {
            $valuePlaceholders = [];
            $params = [];

            foreach ($chunk as $index => $name) {
                $nameParam = ":name{$index}";
                $valuePlaceholders[] = "({$nameParam})";
                $params[$nameParam] = $name;
            }

            $valuesClause = implode(', ', $valuePlaceholders);

            $query = "INSERT INTO {$this->getBaseTable($language)} (name) VALUES {$valuesClause}";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
        }
    }

    public function getBaseTable(string $language): string
    {
        return 'pronunciation_'.$language.'_language';
    }

    public function getLinksTable(string $language): string
    {
        return $language.'_links';
    }

    public function getArticleNames(string $language, int $limit = self::PROCESSING_LIMIT): array
    {
        $query = 'SELECT name,ts_created FROM lingwhaat.'.$this->getBaseTable($language).' 
        WHERE ipa = "" ORDER BY ts_created ASC LIMIT '.$limit;

        $this->connect();
        return $this->fetch($query);

    }

}