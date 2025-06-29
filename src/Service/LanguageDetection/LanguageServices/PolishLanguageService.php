<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\PolishLanguageRepository;

class PolishLanguageService
{
    public function __construct(protected PolishLanguageRepository $polishLanguageRepository)
    {
    }

    public function checkLanguage($word): bool
    {
        $result = $this->polishLanguageRepository->findByName($word);

        if ($result) {
            return true;
        }

        return false;
    }

    public function fetchAllNamesAndIpa(): array
    {
        return $this->polishLanguageRepository->findAllNamesAndIpa();
    }

}