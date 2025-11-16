<?php

namespace App\Service;

use App\Query\LanguageQuery;

class LanguageAddService
{
    public function __construct(protected LanguageQuery $languageQuery)
    {
    }
    public function addLanguage($language): void
    {
        $this->languageQuery->addLanguage($language);
    }

}