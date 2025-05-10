<?php

namespace App\Service;

use App\Query\PronunciationQueryLatvianLanguage;
use App\Query\PronunciationQuerySwedishLanguage;

class WiktionaryArticlesCategoriesSwedishService extends WiktionaryArticlesCategoriesLatvianService
{
    public function __construct(
        protected PronunciationQueryLatvianLanguage $queryLatvianLanguage,
        protected PronunciationQuerySwedishLanguage $querySwedishLanguage
    )
    {
        parent::__construct($queryLatvianLanguage);
    }

    protected function getCmtitle(): string
    {
        return "Category:Swedish_lemmas";
    }

    protected function add($name): void
    {
        $this->querySwedishLanguage->add($name);
    }

}