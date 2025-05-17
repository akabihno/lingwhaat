<?php

namespace App\Service;

use App\Query\PronunciationQueryDutchLanguage;
use App\Query\PronunciationQueryLatvianLanguage;

class WiktionaryArticlesCategoriesDutchService extends WiktionaryArticlesCategoriesLatvianService
{
    public function __construct(
        protected PronunciationQueryLatvianLanguage $queryLatvianLanguage,
        protected PronunciationQueryDutchLanguage $queryDutchLanguage,
    )
    {
        parent::__construct($queryLatvianLanguage);
    }
    protected function getCmtitle(): string
    {
        return "Category:Dutch_lemmas";
    }

    protected function add($name): void
    {
        $this->queryDutchLanguage->add($name);
    }

}