<?php

namespace App\Query;

class PronunciationQueryBengaliLanguage extends PronunciationQueryRussianLanguage
{
    public function getBaseTable(): string
    {
        return 'pronunciation_bengali_language';
    }

    public function getLinksTable(): string
    {
        return 'bengali_links';
    }

}