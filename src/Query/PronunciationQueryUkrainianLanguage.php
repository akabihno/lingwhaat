<?php

namespace App\Query;

class PronunciationQueryUkrainianLanguage extends PronunciationQueryRussianLanguage
{
    public function getBaseTable(): string
    {
        return 'pronunciation_ukrainian_language';
    }

    public function getLinksTable(): string
    {
        return 'ukrainian_links';
    }

}