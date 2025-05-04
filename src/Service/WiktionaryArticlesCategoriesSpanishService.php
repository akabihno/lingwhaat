<?php

namespace App\Service;

use App\Query\PronunciationQueryLatvianLanguage;
use App\Query\PronunciationQuerySpanishLanguage;

class WiktionaryArticlesCategoriesSpanishService extends WiktionaryArticlesCategoriesLatvianService
{
    public function __construct(
        protected PronunciationQueryLatvianLanguage $queryLatvianLanguage,
        protected PronunciationQuerySpanishLanguage $querySpanishLanguage
    )
    {
        parent::__construct($queryLatvianLanguage);
    }
    protected function getCmtitle(): string
    {
        return "Category:Spanish_lemmas";
    }

    protected function add($name): void
    {
        $this->querySpanishLanguage->add($name);
    }

}