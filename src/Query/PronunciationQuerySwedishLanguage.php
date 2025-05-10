<?php

namespace App\Query;

class PronunciationQuerySwedishLanguage extends PronunciationQueryRussianLanguage
{
    public function getBaseTable(): string
    {
        return 'pronunciation_swedish_language';
    }

    public function getLinksTable(): string
    {
        return 'swedish_links';
    }

}