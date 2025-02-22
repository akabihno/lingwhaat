<?php

namespace App\Service;

use App\Query\PronunciationQueryLatvianLanguage;
use App\Query\PronunciationQueryPortugueseLanguage;

class WiktionaryArticlesCategoriesPortugueseService extends WiktionaryArticlesCategoriesLatvianService
{
    public function __construct(
        protected PronunciationQueryLatvianLanguage $queryLatvianLanguage,
        protected PronunciationQueryPortugueseLanguage $queryPortugueseLanguage
    )
    {
        parent::__construct($queryLatvianLanguage);
    }
    protected function getCmtitle(): string
    {
        return "Category:Portuguese_lemmas";
    }

    protected function add($name): void
    {
        $this->queryPortugueseLanguage->add($name);
    }

}