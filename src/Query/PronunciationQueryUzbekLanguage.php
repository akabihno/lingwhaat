<?php

namespace App\Query;

class PronunciationQueryUzbekLanguage extends PronunciationQueryRussianLanguage
{
    public function getBaseTable(): string
    {
        return 'pronunciation_uzbek_language';
    }

    public function getLinksTable(): string
    {
        return 'uzbek_links';
    }

}