<?php

namespace App\Query;

class PronunciationQueryDutchLanguage extends PronunciationQueryRussianLanguage
{
    public function getBaseTable(): string
    {
        return 'pronunciation_dutch_language';
    }

    public function getLinksTable(): string
    {
        return 'dutch_links';
    }

}