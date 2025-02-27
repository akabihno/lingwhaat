<?php

namespace App\Query;

class PronunciationQueryRomanianLanguage extends PronunciationQueryRussianLanguage
{
    protected function getBaseTable(): string
    {
        return 'pronunciation_romanian_language';
    }

    protected function getLinksTable(): string
    {
        return 'romanian_links';
    }

}