<?php

namespace App\Query;

class PronunciationQueryRomanianLanguage extends PronunciationQueryRussianLanguage
{
    public function getBaseTable(): string
    {
        return 'pronunciation_romanian_language';
    }

    public function getLinksTable(): string
    {
        return 'romanian_links';
    }

}