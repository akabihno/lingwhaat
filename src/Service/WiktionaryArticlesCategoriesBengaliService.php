<?php

namespace App\Service;

use App\Query\PronunciationQueryBengaliLanguage;
use App\Query\PronunciationQueryLatvianLanguage;

class WiktionaryArticlesCategoriesBengaliService extends WiktionaryArticlesCategoriesLatvianService
{
    public function __construct(
        protected PronunciationQueryLatvianLanguage $queryLatvianLanguage,
        protected PronunciationQueryBengaliLanguage $queryBengaliLanguage
    )
    {
        parent::__construct($queryLatvianLanguage);
    }
    protected function getCmtitle(): string
    {
        return "Category:Bengali_lemmas";
    }

    protected function add($name): void
    {
        $this->queryBengaliLanguage->add($name);
    }

}