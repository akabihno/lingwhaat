<?php

namespace App\Query;

class PronunciationQueryLatinLanguage extends PronunciationQueryRussianLanguage
{
    public function getBaseTable(): string
    {
        return 'pronunciation_latin_language';
    }

    public function getLinksTable(): string
    {
        return 'latin_links';
    }

}