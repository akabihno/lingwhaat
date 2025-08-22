<?php

namespace App\Query;

class PronunciationQueryAfrikaansLanguage extends PronunciationQueryRussianLanguage
{
    public function getBaseTable(): string
    {
        return 'pronunciation_afrikaans_language';
    }

    public function getLinksTable(): string
    {
        return 'afrikaans_links';
    }

}