<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\GeorgianLanguageRepository;

class GeorgianLanguageService
{
    public function __construct(protected GeorgianLanguageRepository $georgianLanguageRepository)
    {
    }

    public function checkLanguage($word): bool
    {
        return (bool) $this->georgianLanguageRepository->findByName($word);
    }

    public function fetchAllNamesAndIpa(): array
    {
        return $this->georgianLanguageRepository->findAllNamesAndIpa();
    }

}