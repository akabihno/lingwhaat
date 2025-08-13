<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\SwedishLanguageRepository;

class SwedishLanguageService
{
    public function __construct(protected SwedishLanguageRepository $swedishLanguageRepository)
    {
    }

    public function checkLanguage($word): bool
    {
        return (bool) $this->swedishLanguageRepository->findByName($word);
    }

    public function fetchAllNamesAndIpa(): array
    {
        return $this->swedishLanguageRepository->findAllNamesAndIpa();
    }

}