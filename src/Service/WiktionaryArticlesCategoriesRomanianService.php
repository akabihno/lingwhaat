<?php

namespace App\Service;

use App\Query\PronunciationQueryLatvianLanguage;
use App\Query\PronunciationQueryRomanianLanguage;

class WiktionaryArticlesCategoriesRomanianService extends WiktionaryArticlesCategoriesLatvianService
{
    public function __construct(
        protected PronunciationQueryLatvianLanguage $queryLatvianLanguage,
        protected PronunciationQueryRomanianLanguage $queryRomanianLanguage
    )
    {
        parent::__construct($queryLatvianLanguage);
    }
    protected function getCmtitle(): string
    {
        return "Category:Romanian_lemmas";
    }

    protected function add($name): void
    {
        $this->queryRomanianLanguage->add($name);
    }

}