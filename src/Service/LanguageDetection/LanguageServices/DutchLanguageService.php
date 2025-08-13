<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\DutchLanguageRepository;

class DutchLanguageService
{
    public function __construct(protected DutchLanguageRepository $dutchLanguageRepository)
    {
    }

    public function checkLanguage($word): bool
    {
        return (bool) $this->dutchLanguageRepository->findByName($word);
    }

    public function fetchAllNamesAndIpa(): array
    {
        return $this->dutchLanguageRepository->findAllNamesAndIpa();
    }

}