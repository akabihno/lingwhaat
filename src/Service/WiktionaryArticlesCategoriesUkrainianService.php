<?php

namespace App\Service;

use App\Query\PronunciationQueryLatvianLanguage;
use App\Query\PronunciationQueryUkrainianLanguage;

class WiktionaryArticlesCategoriesUkrainianService extends WiktionaryArticlesCategoriesLatvianService
{
    public function __construct(
        protected PronunciationQueryLatvianLanguage $queryLatvianLanguage,
        protected PronunciationQueryUkrainianLanguage $queryUkrainianLanguage
    )
    {
        parent::__construct($queryLatvianLanguage);
    }
    protected function getCmtitle(): string
    {
        return "Category:Ukrainian_lemmas";
    }

    protected function add($name): void
    {
        $this->queryUkrainianLanguage->add($name);
    }

}