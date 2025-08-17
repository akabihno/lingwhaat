<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\LithuanianLanguageRepository;

class LithuanianLanguageService extends AbstractLanguageService
{
    public function __construct(protected LithuanianLanguageRepository $lithuanianLanguageRepository)
    {
    }

    public function checkLanguage($word): bool
    {
        return (bool) $this->lithuanianLanguageRepository->findByName($word);
    }

    public function fetchAllNamesAndIpa(): array
    {
        return $this->lithuanianLanguageRepository->findAllNamesAndIpa();
    }

}