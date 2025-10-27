<?php

namespace App\Service;

use App\Query\PronunciationQueryLatvianLanguage;
use App\Query\PronunciationQueryUzbekLanguage;

class WiktionaryArticlesCategoriesUzbekService extends WiktionaryArticlesCategoriesLatvianService
{
    public function __construct(
        protected PronunciationQueryLatvianLanguage $queryLatvianLanguage,
        protected PronunciationQueryUzbekLanguage $queryUzbekLanguage
    )
    {
        parent::__construct($queryLatvianLanguage);
    }
    protected function getCmtitle(): string
    {
        return "Category:Uzbek_lemmas";
    }

    protected function add($name): void
    {
        $this->queryUzbekLanguage->add($name);
    }

}