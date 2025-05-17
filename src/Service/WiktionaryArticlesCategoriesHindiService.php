<?php

namespace App\Service;

use App\Query\PronunciationQueryHindiLanguage;
use App\Query\PronunciationQueryLatvianLanguage;

class WiktionaryArticlesCategoriesHindiService extends WiktionaryArticlesCategoriesLatvianService
{
    public function __construct(
        protected PronunciationQueryLatvianLanguage $queryLatvianLanguage,
        protected PronunciationQueryHindiLanguage $queryHindiLanguage,
    )
    {
        parent::__construct($queryLatvianLanguage);
    }
    protected function getCmtitle(): string
    {
        return "Category:Hindi_lemmas";
    }

    protected function add($name): void
    {
        $this->queryHindiLanguage->add($name);
    }

}