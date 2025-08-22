<?php

namespace App\Service;

use App\Query\PronunciationQueryAfrikaansLanguage;
use App\Query\PronunciationQueryLatvianLanguage;

class WiktionaryArticlesCategoriesAfrikaansService extends WiktionaryArticlesCategoriesLatvianService
{
    public function __construct(
        protected PronunciationQueryLatvianLanguage $queryLatvianLanguage,
        protected PronunciationQueryAfrikaansLanguage $queryAfrikaansLanguage
    )
    {
        parent::__construct($queryLatvianLanguage);
    }
    protected function getCmtitle(): string
    {
        return "Category:Afrikaans_lemmas";
    }

    protected function add($name): void
    {
        $this->queryAfrikaansLanguage->add($name);
    }

}