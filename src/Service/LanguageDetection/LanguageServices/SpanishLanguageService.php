<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\AbstractLanguageRepository;
use App\Repository\SpanishLanguageRepository;

class SpanishLanguageService extends AbstractLanguageService
{
    public function __construct(protected SpanishLanguageRepository $spanishLanguageRepository)
    {
    }

    public function checkLanguage($word): bool
    {
        return (bool) $this->spanishLanguageRepository->findByName($word);
    }

    protected function getRepository(): AbstractLanguageRepository
    {
        return $this->spanishLanguageRepository;
    }
}