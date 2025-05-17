<?php

namespace App\Query;

class PronunciationQueryHindiLanguage extends PronunciationQueryRussianLanguage
{
    public function getBaseTable(): string
    {
        return 'pronunciation_hindi_language';
    }

    public function getLinksTable(): string
    {
        return 'hindi_links';
    }

}