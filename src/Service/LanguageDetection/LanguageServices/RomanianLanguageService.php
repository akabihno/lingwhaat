<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\RomanianLanguageRepository;

class RomanianLanguageService
{
    public function __construct(protected RomanianLanguageRepository $romanianLanguageRepository)
    {
    }

    public function checkLanguage($word): bool
    {
        return (bool) $this->romanianLanguageRepository->findByName($word);
    }

    public function fetchAllNamesAndIpa(): array
    {
        return $this->romanianLanguageRepository->findAllNamesAndIpa();
    }

}