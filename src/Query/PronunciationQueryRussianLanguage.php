<?php

namespace App\Query;

class PronunciationQueryRussianLanguage extends AbstractQuery
{
    const PROCESSING_LIMIT = 10;
    public function getArticleName(): void
    {
        $query = 'SELECT name,ts_created FROM lingwhaat.'.$this->getTable().' WHERE name LIKE "а%" ORDER BY ts_created ASC LIMIT '.self::PROCESSING_LIMIT;

        $this->connect();
        var_dump($this->fetch($query));

    }

    protected function getTable(): string
    {
        return 'pronunciation_russian_language';
    }

}