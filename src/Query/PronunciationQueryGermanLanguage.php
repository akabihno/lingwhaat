<?php

namespace App\Query;

class PronunciationQueryGermanLanguage extends PronunciationQueryRussianLanguage
{
    public function getBaseTable(): string
    {
        return 'pronunciation_german_language';
    }

    public function getLinksTable(): string
    {
        return 'german_links';
    }

}