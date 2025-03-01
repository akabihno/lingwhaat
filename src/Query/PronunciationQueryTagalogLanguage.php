<?php

namespace App\Query;

class PronunciationQueryTagalogLanguage extends PronunciationQueryRussianLanguage
{
    public function getBaseTable(): string
    {
        return 'pronunciation_tagalog_language';
    }

    public function getLinksTable(): string
    {
        return 'tagalog_links';
    }

}