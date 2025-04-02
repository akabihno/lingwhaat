<?php

namespace App\Service;

use App\Query\PronunciationQueryGreekLanguage;
use App\Query\PronunciationQueryLatvianLanguage;

class WiktionaryArticlesCategoriesGreekService extends WiktionaryArticlesCategoriesLatvianService
{
    public function __construct(
        protected PronunciationQueryLatvianLanguage $queryLatvianLanguage,
        protected PronunciationQueryGreekLanguage $queryGreekLanguage
    )
    {
        parent::__construct($queryLatvianLanguage);
    }
    protected function getCmtitle(): string
    {
        return "Category:Greek_lemmas";
    }

    protected function add($name): void
    {
        $this->queryGreekLanguage->add($name);
    }

}