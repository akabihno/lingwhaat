<?php

namespace App\Query;

class PronunciationQueryRussianLanguage extends AbstractQuery
{
    const PROCESSING_LIMIT = 10;
    public function getArticleName(): void
    {
        $query = 'SELECT name,ts_crated FROM lingwhaat.'.$this->getTable().'WHERE name LIKE "а%" LIMIT '.self::PROCESSING_LIMIT.' ORDER BY ts_crated DESC';

        $this->connect();
        var_dump($this->fetch($query));

    }

    protected function getTable(): string
    {
        return 'pronunciation_russian_language';
    }

}