<?php

namespace App\Service;

use App\Query\PronunciationQueryLatvianLanguage;
use App\Query\PronunciationQueryPolishLanguage;
use App\Query\PronunciationQueryTagalogLanguage;

class WiktionaryArticlesCategoriesTagalogService extends WiktionaryArticlesCategoriesLatvianService
{
    public function __construct(
        protected PronunciationQueryLatvianLanguage $queryLatvianLanguage,
        protected PronunciationQueryTagalogLanguage $queryTagalogLanguage
    )
    {
        parent::__construct($queryLatvianLanguage);
    }
    protected function getCmtitle(): string
    {
        return "Category:Tagalog_lemmas";
    }

    protected function add($name): void
    {
        $this->queryTagalogLanguage->add($name);
    }

}