<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\EnglishLanguageRepository;

class EnglishLanguageService
{
    public function __construct(protected EnglishLanguageRepository $englishLanguageRepository)
    {
    }

    public function checkLanguage($word): bool
    {
        $result = $this->englishLanguageRepository->findByName($word);

        if ($result) {
            return true;
        }

        return false;
    }

    public function fetchAllNamesAndIpa(): array
    {
        return $this->englishLanguageRepository->findAllNamesAndIpa();
    }

}