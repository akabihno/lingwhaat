<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\UkrainianLanguageRepository;

class UkrainianLanguageService extends AbstractLanguageService
{
    public function __construct(protected UkrainianLanguageRepository $ukrainianLanguageRepository)
    {
    }

    public function checkLanguage($word): bool
    {
        return (bool) $this->ukrainianLanguageRepository->findByName($word);
    }

    public function fetchAllNamesAndIpa(): array
    {
        return $this->ukrainianLanguageRepository->findAllNamesAndIpa();
    }

}