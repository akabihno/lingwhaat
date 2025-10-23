<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\AbstractLanguageRepository;
use App\Repository\SpanishLanguageRepository;

class SpanishLanguageService extends AbstractLanguageService
{
    public function __construct(protected SpanishLanguageRepository $spanishLanguageRepository)
    {
    }

    protected function getRepository(): AbstractLanguageRepository
    {
        return $this->spanishLanguageRepository;
    }
}