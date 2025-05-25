<?php

namespace App\Query;

class PronunciationQueryTurkishLanguage extends PronunciationQueryRussianLanguage
{
    public function getBaseTable(): string
    {
        return 'pronunciation_turkish_language';
    }

    public function getLinksTable(): string
    {
        return 'turkish_links';
    }

}