<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\GermanLanguageRepository;

class GermanLanguageService
{
    public function __construct(protected GermanLanguageRepository $germanLanguageRepository)
    {
    }

    public function checkLanguage($word): bool
    {
        return (bool) $this->germanLanguageRepository->findByName($word);
    }

    public function fetchAllNamesAndIpa(): array
    {
        return $this->germanLanguageRepository->findAllNamesAndIpa();
    }


}