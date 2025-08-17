<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\RussianLanguageRepository;

class RussianLanguageService extends AbstractLanguageService
{
    public function __construct(protected RussianLanguageRepository $russianLanguageRepository)
    {
    }

    public function checkLanguage($word): bool
    {
        return (bool) $this->russianLanguageRepository->findByName($word);
    }

    public function fetchAllNamesAndIpa(): array
    {
        return $this->russianLanguageRepository->findAllNamesAndIpa();
    }

}