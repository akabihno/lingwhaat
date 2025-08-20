<?php

namespace App\Query;

class PronunciationQueryCzechLanguage extends PronunciationQueryRussianLanguage
{
    public function getBaseTable(): string
    {
        return 'pronunciation_czech_language';
    }

    public function getLinksTable(): string
    {
        return 'czech_links';
    }

}