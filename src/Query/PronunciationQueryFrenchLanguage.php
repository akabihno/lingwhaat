<?php

namespace App\Query;

class PronunciationQueryFrenchLanguage extends PronunciationQueryRussianLanguage
{
    protected function getBaseTable(): string
    {
        return 'pronunciation_french_language';
    }

    protected function getLinksTable(): string
    {
        return 'french_links';
    }

}