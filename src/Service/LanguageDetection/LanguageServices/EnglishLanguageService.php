<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\EnglishLanguageRepository;

class EnglishLanguageService extends AbstractLanguageService
{
    public function __construct(protected EnglishLanguageRepository $englishLanguageRepository)
    {
    }

    public function checkLanguage($word): bool
    {
        return (bool) $this->englishLanguageRepository->findByName($word);
    }

    public function fetchAllNamesAndIpa(): array
    {
        return $this->englishLanguageRepository->findAllNamesAndIpa();
    }

}