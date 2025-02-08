<?php

namespace App\Query;

class PronunciationQueryLatvianLanguage extends PronunciationQueryRussianLanguage
{
    protected function getBaseTable(): string
    {
        return 'pronunciation_latvian_language';
    }

    protected function getLinksTable(): string
    {
        return 'latvian_links';
    }

    public function add($name): void
    {
        $query = 'INSERT INTO '.$this->getBaseTable().' (name) VALUES (:name)';

        $this->insertNames($query, $name);
    }

}