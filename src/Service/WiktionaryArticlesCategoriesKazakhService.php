<?php

namespace App\Service;

use App\Query\PronunciationQueryKazakhLanguage;
use App\Query\PronunciationQueryLatvianLanguage;

class WiktionaryArticlesCategoriesKazakhService extends WiktionaryArticlesCategoriesLatvianService
{
    public function __construct(
        protected PronunciationQueryLatvianLanguage $queryLatvianLanguage,
        protected PronunciationQueryKazakhLanguage $queryKazakhLanguage
    )
    {
        parent::__construct($queryLatvianLanguage);
    }
    protected function getCmtitle(): string
    {
        return "Category:Kazakh_lemmas";
    }

    protected function add($name): void
    {
        $this->queryKazakhLanguage->add($name);
    }

}