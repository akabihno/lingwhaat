<?php

namespace App\Query;

class PronunciationQueryLithuanianLanguage extends PronunciationQueryRussianLanguage
{
    public function getBaseTable(): string
    {
        return 'pronunciation_lithuanian_language';
    }

    public function getLinksTable(): string
    {
        return 'lithuanian_links';
    }

}