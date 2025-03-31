<?php

namespace App\Service;

use App\Query\PronunciationQueryLatvianLanguage;
use App\Query\PronunciationQueryLithuanianLanguage;

class WiktionaryArticlesCategoriesLithuanianService extends WiktionaryArticlesCategoriesLatvianService
{
    public function __construct(
        protected PronunciationQueryLatvianLanguage $queryLatvianLanguage,
        protected PronunciationQueryLithuanianLanguage $queryLithuanianLanguage
    )
    {
        parent::__construct($queryLatvianLanguage);
    }
    protected function getCmtitle(): string
    {
        return "Category:Lithuanian_lemmas";
    }

    protected function add($name): void
    {
        $this->queryLithuanianLanguage->add($name);
    }

}