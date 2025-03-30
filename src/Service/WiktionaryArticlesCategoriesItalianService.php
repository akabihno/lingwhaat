<?php

namespace App\Service;

use App\Query\PronunciationQueryItalianLanguage;
use App\Query\PronunciationQueryLatvianLanguage;

class WiktionaryArticlesCategoriesItalianService extends WiktionaryArticlesCategoriesLatvianService
{
    public function __construct(
        protected PronunciationQueryLatvianLanguage $queryLatvianLanguage,
        protected PronunciationQueryItalianLanguage $queryItalianLanguage
    )
    {
        parent::__construct($queryLatvianLanguage);
    }
    protected function getCmtitle(): string
    {
        return "Category:Italian_lemmas";
    }

    protected function add($name): void
    {
        $this->queryItalianLanguage->add($name);
    }

}