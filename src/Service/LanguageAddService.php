<?php

namespace App\Service;

use App\Query\NewLanguageQuery;

class LanguageAddService
{
    public function __construct(protected NewLanguageQuery $languageQuery)
    {
    }
    public function addLanguage($language): void
    {
        $this->languageQuery->addLanguage($language);
    }

}