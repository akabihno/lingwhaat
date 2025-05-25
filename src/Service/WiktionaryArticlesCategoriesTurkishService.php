<?php

namespace App\Service;

use App\Query\PronunciationQueryTurkishLanguage;
use App\Query\PronunciationQueryLatvianLanguage;

class WiktionaryArticlesCategoriesTurkishService extends WiktionaryArticlesCategoriesLatvianService
{
    public function __construct(
        protected PronunciationQueryLatvianLanguage $queryLatvianLanguage,
        protected PronunciationQueryTurkishLanguage $queryTurkishLanguage,
    )
    {
        parent::__construct($queryLatvianLanguage);
    }
    protected function getCmtitle(): string
    {
        return "Category:Turkish_lemmas";
    }

    protected function add($name): void
    {
        $this->queryTurkishLanguage->add($name);
    }

}