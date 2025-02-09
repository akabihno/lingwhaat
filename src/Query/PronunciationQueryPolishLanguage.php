<?php

namespace App\Query;

class PronunciationQueryPolishLanguage extends PronunciationQueryRussianLanguage
{
    protected function getBaseTable(): string
    {
        return 'pronunciation_polish_language';
    }

    protected function getLinksTable(): string
    {
        return 'polish_links';
    }

}