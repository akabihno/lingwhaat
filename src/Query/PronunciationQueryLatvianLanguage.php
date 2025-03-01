<?php

namespace App\Query;

class PronunciationQueryLatvianLanguage extends PronunciationQueryRussianLanguage
{
    public function getBaseTable(): string
    {
        return 'pronunciation_latvian_language';
    }

    public function getLinksTable(): string
    {
        return 'latvian_links';
    }

}