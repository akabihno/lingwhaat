<?php

namespace App\Query;

class LanguageQuery extends AbstractQuery
{
    public function addLanguage($language): void
    {
        $this->connect();

        $query = 'CREATE TABLE pronunciation_'.$language.'_language ( id INT(10) AUTO_INCREMENT PRIMARY KEY, name VARCHAR(256) DEFAULT "", ipa VARCHAR(2048) DEFAULT "", ts_created TIMESTAMP DEFAULT NOW() );';
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();

        $query = 'ALTER TABLE pronunciation_'.$language.'_language ADD INDEX i_name (name);';
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();

        $query = 'CREATE TABLE '.$language.'_links (id int NOT NULL AUTO_INCREMENT, name varchar(256) DEFAULT "", link varchar(2048) DEFAULT "", ts_created timestamp NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (id)) ENGINE=InnoDB AUTO_INCREMENT=440355 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci';
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
    }

}