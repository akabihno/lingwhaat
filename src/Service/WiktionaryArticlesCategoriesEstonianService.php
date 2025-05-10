<?php

namespace App\Service;

use App\Query\PronunciationQueryEstonianLanguage;
use App\Query\PronunciationQueryLatvianLanguage;

class WiktionaryArticlesCategoriesEstonianService extends WiktionaryArticlesCategoriesLatvianService
{
    public function __construct(
        protected PronunciationQueryLatvianLanguage $queryLatvianLanguage,
        protected PronunciationQueryEstonianLanguage $queryEstonianLanguage,
    )
    {
        parent::__construct($queryLatvianLanguage);
    }
    protected function getCmtitle(): string
    {
        return "Category:Estonian_lemmas";
    }

    protected function add($name): void
    {
        $this->queryEstonianLanguage->add($name);
    }

}