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
        Dotenv::createImmutable('/var/www/html/')->load();

        $dbHost = $_ENV['DB_HOST'];
        $dbPort = $_ENV['MYSQL_PORT'];
        $dbName = $_ENV['MYSQL_DATABASE'];
        $dbPassword = $_ENV['MYSQL_ROOT_PASSWORD'];

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $dbHost, $dbPort, $dbName);
        $username = 'root';

        try {
            $this->pdo = new PDO($dsn, $username, $dbPassword);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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

    public function updateIpa(string $language, string $ipa, string $name): void
    {
        $this->connect();
        $query = 'UPDATE '.$this->getBaseTable($language).' SET ipa = :ipa, ts_created = NOW() WHERE name = :name';

        $stmt = $this->pdo->prepare($query);

        $stmt->execute([
            ':ipa' => $ipa,
            ':name' => $name
        ]);
    }

    public function insertLinks(string $language, $name, $link): void
    {
        $this->connect();
        $query = 'INSERT INTO '.$this->getLinksTable($language).' (name, link) VALUES (:name, :link)';

        $stmt = $this->pdo->prepare($query);

        $stmt->execute([
            ':name' => $name,
            ':link' => $link
        ]);

    }

    public function insertNames(string $language, string $name): void
    {
        $this->connect();
        $query = 'INSERT INTO '.$this->getBaseTable($language).' (name) VALUES (:name)';

        $stmt = $this->pdo->prepare($query);

        $stmt->execute([
            ':name' => $name
        ]);
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
        $query = 'SELECT name,ts_created FROM lingwhaat.'.$this->getBaseTable($language).' ORDER BY ts_created ASC LIMIT '.$limit;

        $this->connect();
        return $this->fetch($query);

    }

}