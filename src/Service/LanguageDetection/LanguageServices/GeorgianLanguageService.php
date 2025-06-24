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
        $result = $this->georgianLanguageRepository->findByName($word);

        if ($result) {
            return true;
        }

        return false;
    }

    public function fetchAllNamesAndIpa(): array
    {
        return $this->georgianLanguageRepository->findAllNamesAndIpa();
    }

}