<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\LatvianLanguageRepository;

class LatvianLanguageService
{
    public function __construct(protected LatvianLanguageRepository $latvianLanguageRepository)
    {
    }

    public function checkLanguage($word): bool
    {
        return (bool) $this->latvianLanguageRepository->findByName($word);
    }

    public function fetchAllNamesAndIpa(): array
    {
        return $this->latvianLanguageRepository->findAllNamesAndIpa();
    }

}