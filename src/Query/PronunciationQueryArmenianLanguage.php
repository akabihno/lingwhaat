<?php

namespace App\Query;

class PronunciationQueryArmenianLanguage extends PronunciationQueryRussianLanguage
{
    public function getBaseTable(): string
    {
        return 'pronunciation_armenian_language';
    }

    public function getLinksTable(): string
    {
        return 'armenian_links';
    }

}