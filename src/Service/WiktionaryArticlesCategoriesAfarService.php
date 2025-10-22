<?php

namespace App\Service;

use App\Query\PronunciationQueryAfarLanguage;
use App\Query\PronunciationQueryLatvianLanguage;

class WiktionaryArticlesCategoriesAfarService extends WiktionaryArticlesCategoriesLatvianService
{
    public function __construct(
        protected PronunciationQueryLatvianLanguage $queryLatvianLanguage,
        protected PronunciationQueryAfarLanguage $queryAfarLanguage
    )
    {
        parent::__construct($queryLatvianLanguage);
    }
    protected function getCmtitle(): string
    {
        return "Category:Afar_lemmas";
    }

    protected function add($name): void
    {
        $this->queryAfarLanguage->add($name);
    }

}