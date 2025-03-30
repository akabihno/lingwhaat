<?php

namespace App\Query;

class PronunciationQueryItalianLanguage extends PronunciationQueryRussianLanguage
{
    public function getBaseTable(): string
    {
        return 'pronunciation_italian_language';
    }

    public function getLinksTable(): string
    {
        return 'italian_links';
    }

}