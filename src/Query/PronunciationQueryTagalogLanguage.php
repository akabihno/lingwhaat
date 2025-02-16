<?php

namespace App\Query;

class PronunciationQueryTagalogLanguage extends PronunciationQueryRussianLanguage
{
    protected function getBaseTable(): string
    {
        return 'pronunciation_tagalog_language';
    }

    protected function getLinksTable(): string
    {
        return 'tagalog_links';
    }

}