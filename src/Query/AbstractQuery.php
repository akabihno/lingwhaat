<?php

namespace App\Query;

use PDO;
use PDOException;

class AbstractQuery
{
    const DB_PORT = 3327;
    protected $pdo;
    protected function connect()
    {
        \Dotenv\Dotenv::createImmutable('/var/www/html/')->load();

        $dbHost = $_ENV['DB_HOST'];
        $dbName = $_ENV['MYSQL_DATABASE'];
        $dbPassword = $_ENV['MYSQL_ROOT_PASSWORD'];

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $dbHost, self::DB_PORT, $dbName);
        $username = 'root';

        try {
            $this->pdo = new PDO($dsn, $username, $dbPassword);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            echo 'Connected successfully';
        } catch (PDOException $e) {
            echo 'Connection failed: ' . $e->getMessage();
        }
    }

    protected function fetch($sql): array
    {
        $result = [];
        $stmt = $this->pdo->query($sql);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[] = $row;
        }

        return $result;
    }

    protected function updateIpa(string $query, string $ipa, string $name): void
    {
        $stmt = $this->pdo->prepare($query);

        $stmt->execute([
            ':ipa' => $ipa,
            ':name' => $name
        ]);
    }

    protected function insertLinks(string $query, $name, $link): void
    {
        $stmt = $this->pdo->prepare($query);

        $stmt->execute([
            ':name' => $name,
            ':link' => $link
        ]);

    }

}