<?php

namespace App\Query;

class PronunciationQueryRussianLanguage extends AbstractQuery
{
    const PROCESSING_LIMIT = 2;
    public function getArticleNames(): array
    {
        $query = 'SELECT name,ts_created FROM lingwhaat.'.$this->getTable().' WHERE name LIKE "а%" ORDER BY ts_created ASC LIMIT '.self::PROCESSING_LIMIT;

        $this->connect();
        return $this->fetch($query);

    }

    public function insert(string $name, string $link)
    {
        $query = 'INSERT INTO '.$this->getTable().' (name, link) VALUES (:name, :link)';

        $this->insertLinks($query, $name, $link);

    }

    public function update(string $ipa, string $name)
    {
        $query = 'UPDATE '.$this->getTable().' SET ipa = :ipa WHERE name = :name';

        $this->connect();
        $this->updateIpa($query, $ipa, $name);
    }

    protected function getTable(): string
    {
        return 'pronunciation_russian_language';
    }

}