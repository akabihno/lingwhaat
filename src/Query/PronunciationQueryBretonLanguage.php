<?php

namespace App\Query;

class PronunciationQueryBretonLanguage extends PronunciationQueryRussianLanguage
{
    public function getBaseTable(): string
    {
        return 'pronunciation_breton_language';
    }

    public function getLinksTable(): string
    {
        return 'breton_links';
    }

}