<?php

namespace App\Query;

class PronunciationQueryUkrainianLanguage extends PronunciationQueryRussianLanguage
{
    protected function getBaseTable(): string
    {
        return 'pronunciation_ukrainian_language';
    }

    protected function getLinksTable(): string
    {
        return 'ukrainian_links';
    }

}