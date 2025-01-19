<?php

namespace App\Query;

class PronunciationQueryRussianLanguage extends AbstractQuery
{
    const PROCESSING_LIMIT = 10;
    public function getArticleName(): void
    {
        $query = 'SELECT * FROM lingwhaat.'.$this->getTable().' LIMIT '.self::PROCESSING_LIMIT;

        $this->connect();
        var_dump($this->fetch($query));

    }

    protected function getTable(): string
    {
        return 'pronunciation_russian_language';
    }

}