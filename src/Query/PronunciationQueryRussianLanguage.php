<?php

namespace App\Query;

class PronunciationQueryRussianLanguage extends AbstractQuery
{
    const PROCESSING_LIMIT = 5;
    public function getArticleNames(): array
    {
        $query = 'SELECT name,ts_created FROM lingwhaat.'.$this->getBaseTable().' WHERE name LIKE "в%" ORDER BY ts_created ASC LIMIT '.self::PROCESSING_LIMIT;

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

    protected function getBaseTable(): string
    {
        return 'pronunciation_russian_language';
    }

    protected function getLinksTable(): string
    {
        return 'russian_links';
    }

}