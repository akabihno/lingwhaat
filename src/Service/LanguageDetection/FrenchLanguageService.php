<?php

namespace App\Service\LanguageDetection;

use App\Repository\FrenchLanguageRepository;

class FrenchLanguageService
{
    public function __construct(protected FrenchLanguageRepository $frenchLanguageRepository)
    {
    }

    public function checkLanguage($word): bool
    {
        $result = $this->frenchLanguageRepository->findByName($word);

        if ($result) {
            return true;
        }

        return false;
    }

}