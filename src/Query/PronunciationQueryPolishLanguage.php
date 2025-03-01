<?php

namespace App\Query;

class PronunciationQueryPolishLanguage extends PronunciationQueryRussianLanguage
{
    public function getBaseTable(): string
    {
        return 'pronunciation_polish_language';
    }

    public function getLinksTable(): string
    {
        return 'polish_links';
    }

}