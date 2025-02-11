<?php

namespace App\Service;

use App\Query\PronunciationQueryFrenchLanguage;
use App\Query\PronunciationQueryGermanLanguage;
use App\Query\PronunciationQueryLatvianLanguage;

class WiktionaryArticlesCategoriesFrenchService extends WiktionaryArticlesCategoriesLatvianService
{
    public function __construct(
        protected PronunciationQueryLatvianLanguage $queryLatvianLanguage,
        protected PronunciationQueryFrenchLanguage $queryFrenchLanguage,
    )
    {
        parent::__construct($queryLatvianLanguage);
    }
    protected function getCmtitle(): string
    {
        return "Category:French_lemmas";
    }

    protected function add($name): void
    {
        $this->queryFrenchLanguage->add($name);
    }

}