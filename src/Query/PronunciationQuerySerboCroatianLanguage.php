<?php

namespace App\Query;

class PronunciationQuerySerboCroatianLanguage extends PronunciationQueryRussianLanguage
{
    protected function getBaseTable(): string
    {
        return 'pronunciation_serbocroatian_language';
    }

    protected function getLinksTable(): string
    {
        return 'serbocroatian_links';
    }

}