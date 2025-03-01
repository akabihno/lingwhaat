<?php

namespace App\Query;

class PronunciationQuerySerboCroatianLanguage extends PronunciationQueryRussianLanguage
{
    public function getBaseTable(): string
    {
        return 'pronunciation_serbocroatian_language';
    }

    public function getLinksTable(): string
    {
        return 'serbocroatian_links';
    }

}