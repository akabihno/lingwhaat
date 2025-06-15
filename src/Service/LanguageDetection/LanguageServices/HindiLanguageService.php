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
        $result = $this->hindiLanguageRepository->findByName($word);

        if ($result) {
            return true;
        }

        return false;
    }

    public function fetchAllNamesAndIpa(): array
    {
        return $this->hindiLanguageRepository->findAllNamesAndIpa();
    }

}