<?php

namespace App\Query;

class PronunciationQueryEnglishLanguage extends PronunciationQueryRussianLanguage
{
    public function getBaseTable(): string
    {
        return 'pronunciation_english_language';
    }

    public function getLinksTable(): string
    {
        return 'english_links';
    }

}