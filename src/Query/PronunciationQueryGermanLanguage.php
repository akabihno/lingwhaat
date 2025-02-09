<?php

namespace App\Query;

class PronunciationQueryGermanLanguage extends PronunciationQueryRussianLanguage
{
    protected function getBaseTable(): string
    {
        return 'pronunciation_german_language';
    }

    protected function getLinksTable(): string
    {
        return 'german_links';
    }

}