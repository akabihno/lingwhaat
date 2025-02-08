<?php

namespace App\Query;

class PronunciationQueryPolishLanguage extends PronunciationQueryRussianLanguage
{
    protected function getBaseTable(): string
    {
        return 'pronunciation_polish_language';
    }

    protected function getLinksTable(): string
    {
        return 'polish_links';
    }

    public function add($name): void
    {
        $this->connect();
        $query = 'INSERT INTO '.$this->getBaseTable().' (name) VALUES (:name)';

        $this->insertNames($query, $name);
    }

}