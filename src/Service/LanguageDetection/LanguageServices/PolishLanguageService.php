<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\PolishLanguageRepository;

class PolishLanguageService extends AbstractLanguageService
{
    public function __construct(protected PolishLanguageRepository $polishLanguageRepository)
    {
    }

    public function checkLanguage($word): bool
    {
        return (bool) $this->polishLanguageRepository->findByName($word);
    }

    public function fetchAllNamesAndIpa(): array
    {
        return $this->polishLanguageRepository->findAllNamesAndIpa();
    }

}