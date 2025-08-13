<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\SpanishLanguageRepository;

class SpanishLanguageService
{
    public function __construct(protected SpanishLanguageRepository $spanishLanguageRepository)
    {
    }

    public function checkLanguage($word): bool
    {
        return (bool) $this->spanishLanguageRepository->findByName($word);
    }

    public function fetchAllNamesAndIpa(): array
    {
        return $this->spanishLanguageRepository->findAllNamesAndIpa();
    }

}