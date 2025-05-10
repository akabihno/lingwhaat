<?php

namespace App\Query;

class PronunciationQueryEstonianLanguage extends PronunciationQueryRussianLanguage
{
    public function getBaseTable(): string
    {
        return 'pronunciation_estonian_language';
    }

    public function getLinksTable(): string
    {
        return 'estonian_links';
    }

}