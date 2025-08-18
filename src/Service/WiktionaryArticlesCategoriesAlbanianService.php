<?php

namespace App\Service;

use App\Query\PronunciationQueryAlbanianLanguage;
use App\Query\PronunciationQueryLatvianLanguage;

class WiktionaryArticlesCategoriesAlbanianService extends WiktionaryArticlesCategoriesLatvianService
{
    public function __construct(
        protected PronunciationQueryLatvianLanguage $queryLatvianLanguage,
        protected PronunciationQueryAlbanianLanguage $queryAlbanianLanguage
    )
    {
        parent::__construct($queryLatvianLanguage);
    }
    protected function getCmtitle(): string
    {
        return "Category:Albanian_lemmas";
    }

    protected function add($name): void
    {
        $this->queryAlbanianLanguage->add($name);
    }

}