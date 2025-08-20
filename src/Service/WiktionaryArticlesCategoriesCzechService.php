<?php

namespace App\Service;

use App\Query\PronunciationQueryCzechLanguage;
use App\Query\PronunciationQueryLatvianLanguage;

class WiktionaryArticlesCategoriesCzechService extends WiktionaryArticlesCategoriesLatvianService
{
    public function __construct(
        protected PronunciationQueryLatvianLanguage $queryLatvianLanguage,
        protected PronunciationQueryCzechLanguage $queryCzechLanguage
    )
    {
        parent::__construct($queryLatvianLanguage);
    }
    protected function getCmtitle(): string
    {
        return "Category:Czech_lemmas";
    }

    protected function add($name): void
    {
        $this->queryCzechLanguage->add($name);
    }

}