<?php

namespace App\Query;

class PronunciationQueryKazakhLanguage extends PronunciationQueryRussianLanguage
{
    public function getBaseTable(): string
    {
        return 'pronunciation_kazakh_language';
    }

    public function getLinksTable(): string
    {
        return 'kazakh_links';
    }
}