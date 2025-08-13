<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\HindiLanguageRepository;

class HindiLanguageService
{
    public function __construct(protected HindiLanguageRepository $hindiLanguageRepository)
    {
    }

    public function checkLanguage($word): bool
    {
        return (bool) $this->hindiLanguageRepository->findByName($word);
    }

    public function fetchAllNamesAndIpa(): array
    {
        return $this->hindiLanguageRepository->findAllNamesAndIpa();
    }

}