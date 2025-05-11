<?php

namespace App\Service;

use App\Query\PronunciationQueryEnglishLanguage;
use App\Query\PronunciationQueryLatvianLanguage;

class WiktionaryArticlesCategoriesEnglishService extends WiktionaryArticlesCategoriesLatvianService
{
    public function __construct(
        protected PronunciationQueryLatvianLanguage $queryLatvianLanguage,
        protected PronunciationQueryEnglishLanguage $queryEnglishLanguage,
    )
    {
        parent::__construct($queryLatvianLanguage);
    }
    protected function getCmtitle(): string
    {
        return "Category:English_lemmas";
    }

    protected function add($name): void
    {
        $this->queryEnglishLanguage->add($name);
    }

}