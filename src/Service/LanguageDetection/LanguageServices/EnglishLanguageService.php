<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\AbstractLanguageRepository;
use App\Repository\EnglishLanguageRepository;

class EnglishLanguageService extends AbstractLanguageService
{
    public function __construct(protected EnglishLanguageRepository $englishLanguageRepository)
    {
    }

    protected function getRepository(): AbstractLanguageRepository
    {
        return $this->englishLanguageRepository;
    }
}