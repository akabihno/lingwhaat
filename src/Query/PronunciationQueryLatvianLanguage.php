<?php

namespace App\Query;

class PronunciationQueryLatvianLanguage extends PronunciationQueryRussianLanguage
{
    protected function getBaseTable(): string
    {
        return 'pronunciation_latvian_language';
    }

    protected function getLinksTable(): string
    {
        return 'latvian_links';
    }

}