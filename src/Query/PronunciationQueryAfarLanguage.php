<?php

namespace App\Query;

class PronunciationQueryAfarLanguage extends PronunciationQueryRussianLanguage
{
    public function getBaseTable(): string
    {
        return 'pronunciation_afar_language';
    }

    public function getLinksTable(): string
    {
        return 'afar_links';
    }

}