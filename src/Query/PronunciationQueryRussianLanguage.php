<?php

namespace App\Query;

class PronunciationQueryRussianLanguage extends AbstractQuery
{
    public function getArticleName(): void
    {
        $query = 'SELECT * FROM lingwhaat.'.$this->getTable().' LIMIT 100';

        $this->connect();
        $this->fetch($query);

    }

    protected function getTable(): string
    {
        return 'pronunciation_russian_language';
    }

}