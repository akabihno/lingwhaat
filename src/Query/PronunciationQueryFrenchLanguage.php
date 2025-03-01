<?php

namespace App\Query;

class PronunciationQueryFrenchLanguage extends PronunciationQueryRussianLanguage
{
    public function getBaseTable(): string
    {
        return 'pronunciation_french_language';
    }

    public function getLinksTable(): string
    {
        return 'french_links';
    }

}