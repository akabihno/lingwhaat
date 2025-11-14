<?php

namespace App\Service;

use App\Query\PronunciationQueryBretonLanguage;
use App\Query\PronunciationQueryLatvianLanguage;

class WiktionaryArticlesCategoriesBretonService extends WiktionaryArticlesCategoriesLatvianService
{
    public function __construct(
        protected PronunciationQueryLatvianLanguage $queryLatvianLanguage,
        protected PronunciationQueryBretonLanguage $queryBretonLanguage
    )
    {
        parent::__construct($queryLatvianLanguage);
    }
    protected function getCmtitle(): string
    {
        return "Category:Breton_lemmas";
    }

    protected function add($name): void
    {
        $this->queryBretonLanguage->add($name);
    }

}