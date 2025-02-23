<?php

namespace App\Service;

use App\Query\PronunciationQueryLatvianLanguage;
use App\Query\PronunciationQuerySerboCroatianLanguage;

class WiktionaryArticlesCategoriesSerboCroatianService extends WiktionaryArticlesCategoriesLatvianService
{
    public function __construct(
        protected PronunciationQueryLatvianLanguage $queryLatvianLanguage,
        protected PronunciationQuerySerboCroatianLanguage $querySerboCroatianLanguage
    )
    {
        parent::__construct($queryLatvianLanguage);
    }
    protected function getCmtitle(): string
    {
        return "Category:Serbo-Croatian_lemmas";
    }

    protected function add($name): void
    {
        $this->querySerboCroatianLanguage->add($name);
    }

}