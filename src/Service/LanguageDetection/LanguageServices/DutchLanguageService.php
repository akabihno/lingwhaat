<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\DutchLanguageRepository;

class DutchLanguageService
{
    public function __construct(protected DutchLanguageRepository $dutchLanguageRepository)
    {
    }

    public function checkLanguage($word): bool
    {
        $result = $this->dutchLanguageRepository->findByName($word);

        if ($result) {
            return true;
        }

        return false;
    }

}