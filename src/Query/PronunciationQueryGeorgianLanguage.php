<?php

namespace App\Query;

class PronunciationQueryGeorgianLanguage extends PronunciationQueryRussianLanguage
{
    public function getBaseTable(): string
    {
        return 'pronunciation_georgian_language';
    }

    public function getLinksTable(): string
    {
        return 'georgian_links';
    }

}