<?php

namespace App\Service;

use App\Query\PronunciationQueryGeorgianLanguage;
use App\Query\PronunciationQueryLatvianLanguage;

class WiktionaryArticlesCategoriesGeorgianService extends WiktionaryArticlesCategoriesLatvianService
{
    public function __construct(
        protected PronunciationQueryLatvianLanguage $queryLatvianLanguage,
        protected PronunciationQueryGeorgianLanguage $queryGeorgianLanguage,
    )
    {
        parent::__construct($queryLatvianLanguage);
    }
    protected function getCmtitle(): string
    {
        return "Category:Georgian_lemmas";
    }

    protected function add($name): void
    {
        $this->queryGeorgianLanguage->add($name);
    }

}