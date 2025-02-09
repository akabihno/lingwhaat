<?php

namespace App\Service;

use App\Query\PronunciationQueryGermanLanguage;
use App\Query\PronunciationQueryLatvianLanguage;

class WiktionaryArticlesCategoriesGermanService extends WiktionaryArticlesCategoriesLatvianService
{
    public function __construct(
        protected PronunciationQueryLatvianLanguage $queryLatvianLanguage,
        protected PronunciationQueryGermanLanguage $queryGermanLanguage,
    )
    {
        parent::__construct($queryLatvianLanguage);
    }
    protected function getCmtitle(): string
    {
        return "Category:German_lemmas";
    }

    protected function add($name): void
    {
        $this->queryGermanLanguage->add($name);
    }

}