<?php

namespace App\Query;

class PronunciationQueryGreekLanguage extends PronunciationQueryRussianLanguage
{
    public function getBaseTable(): string
    {
        return 'pronunciation_greek_language';
    }

    public function getLinksTable(): string
    {
        return 'greek_links';
    }

}