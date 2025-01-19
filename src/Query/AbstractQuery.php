<?php

namespace App\Query;

use PDO;
use PDOException;

class AbstractQuery
{
    protected $pdo;
    protected function connect()
    {
        \Dotenv\Dotenv::createImmutable('/var/www/html/')->load();

        $dbHost = $_ENV['DB_HOST'];
        $dbName = $_ENV['MYSQL_DATABASE'];
        $dbPassword = $_ENV['MYSQL_ROOT_PASSWORD'];

        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $dbHost, $dbName);
        $username = "root";

        try {
            $this->pdo = new PDO($dsn, $username, $dbPassword);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            echo "Connected successfully";
        } catch (PDOException $e) {
            echo "Connection failed: " . $e->getMessage();
        }
    }

    protected function fetch($sql)
    {
        $stmt = $this->pdo->query($sql);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "id: " . $row["id"] . " - Name: " . $row["name"] . "<br>";
        }
    }

}