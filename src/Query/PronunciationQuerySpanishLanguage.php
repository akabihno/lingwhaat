<?php

namespace App\Query;

class PronunciationQuerySpanishLanguage extends PronunciationQueryRussianLanguage
{
    public function getBaseTable(): string
    {
        return 'pronunciation_spanish_language';
    }

    public function getLinksTable(): string
    {
        return 'spanish_links';
    }

}