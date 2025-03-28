<?php

namespace App\Query;

class PronunciationQueryRussianLanguage extends AbstractQuery
{
    const PROCESSING_LIMIT = 40;
    public function getArticleNames(): array
    {
        $query = 'SELECT name,ts_created FROM lingwhaat.'.$this->getBaseTable().' ORDER BY ts_created ASC LIMIT '.self::PROCESSING_LIMIT;

        $this->connect();
        return $this->fetch($query);

    }

    public function insert(string $name, string $link)
    {
        $query = 'INSERT INTO '.$this->getLinksTable().' (name, link) VALUES (:name, :link)';

        $this->insertLinks($query, $name, $link);

    }

    public function update(string $ipa, string $name)
    {
        $query = 'UPDATE '.$this->getBaseTable().' SET ipa = :ipa, ts_created = NOW() WHERE name = :name';

        $this->connect();
        $this->updateIpa($query, $ipa, $name);
    }

    public function getBaseTable(): string
    {
        return 'pronunciation_russian_language';
    }

    public function getLinksTable(): string
    {
        return 'russian_links';
    }

    public function add($name): void
    {
        $this->connect();
        $query = 'INSERT INTO '.$this->getBaseTable().' (name) VALUES (:name)';

        $this->insertNames($query, $name);
    }

}