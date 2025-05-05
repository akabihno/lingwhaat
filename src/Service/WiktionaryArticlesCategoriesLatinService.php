<?php

namespace App\Service;

use App\Query\PronunciationQueryLatinLanguage;
use App\Query\PronunciationQueryLatvianLanguage;

class WiktionaryArticlesCategoriesLatinService extends WiktionaryArticlesCategoriesLatvianService
{
    public function __construct(
        protected PronunciationQueryLatvianLanguage $queryLatvianLanguage,
        protected PronunciationQueryLatinLanguage $queryLatinLanguage
    )
    {
        parent::__construct($queryLatvianLanguage);
    }
    protected function getCmtitle(): string
    {
        return "Category:Latin_lemmas";
    }

    protected function add($name): void
    {
        $this->queryLatinLanguage->add($name);
    }

}