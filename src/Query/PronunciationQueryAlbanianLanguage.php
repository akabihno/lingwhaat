<?php

namespace App\Query;

class PronunciationQueryAlbanianLanguage extends PronunciationQueryRussianLanguage
{
    public function getBaseTable(): string
    {
        return 'pronunciation_albanian_language';
    }

    public function getLinksTable(): string
    {
        return 'albanian_links';
    }

}