<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\EsuLanguageRepository;

class EsuLanguageService
{
    public function __construct(protected EsuLanguageRepository $esuLanguageRepository)
    {
    }

    public function checkLanguage($word): bool
    {
        $result = $this->esuLanguageRepository->findByName($word);

        if ($result) {
            return true;
        }

        return false;
    }

    public function fetchAllNamesAndIpa(): array
    {
        return $this->esuLanguageRepository->findAllNamesAndIpa();
    }

}