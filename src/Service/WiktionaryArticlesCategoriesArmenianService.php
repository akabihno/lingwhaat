<?php

namespace App\Service;

use App\Query\PronunciationQueryArmenianLanguage;
use App\Query\PronunciationQueryLatvianLanguage;

class WiktionaryArticlesCategoriesArmenianService extends WiktionaryArticlesCategoriesLatvianService
{
    public function __construct(
        protected PronunciationQueryLatvianLanguage $queryLatvianLanguage,
        protected PronunciationQueryArmenianLanguage $queryArmenianLanguage,
    )
    {
        parent::__construct($queryLatvianLanguage);
    }
    protected function getCmtitle(): string
    {
        return "Category:Armenian_lemmas";
    }

    protected function add($name): void
    {
        $this->queryArmenianLanguage->add($name);
    }

}