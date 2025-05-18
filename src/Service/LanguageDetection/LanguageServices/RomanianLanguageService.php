<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\RomanianLanguageRepository;

class RomanianLanguageService
{
    public function __construct(protected RomanianLanguageRepository $romanianLanguageRepository)
    {
    }

    public function checkLanguage($word): bool
    {
        $result = $this->romanianLanguageRepository->findByName($word);

        if ($result) {
            return true;
        }

        return false;
    }

}