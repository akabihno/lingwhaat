<?php

namespace App\Service\LanguageDetection;

use App\Repository\SwedishLanguageRepository;

class SwedishLanguageService
{
    public function __construct(protected SwedishLanguageRepository $swedishLanguageRepository)
    {
    }

    public function checkLanguage($word): bool
    {
        $result = $this->swedishLanguageRepository->findByName($word);

        if ($result) {
            return true;
        }

        return false;
    }

}