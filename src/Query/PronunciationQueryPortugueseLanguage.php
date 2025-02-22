<?php

namespace App\Query;

class PronunciationQueryPortugueseLanguage extends PronunciationQueryRussianLanguage
{
    protected function getBaseTable(): string
    {
        return 'pronunciation_portuguese_language';
    }

    protected function getLinksTable(): string
    {
        return 'portuguese_links';
    }

}