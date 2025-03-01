<?php

namespace App\Query;

class PronunciationQueryPortugueseLanguage extends PronunciationQueryRussianLanguage
{
    public function getBaseTable(): string
    {
        return 'pronunciation_portuguese_language';
    }

    public function getLinksTable(): string
    {
        return 'portuguese_links';
    }

}