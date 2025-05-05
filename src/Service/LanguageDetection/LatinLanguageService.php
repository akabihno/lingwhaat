<?php

namespace App\Service\LanguageDetection;

use App\Repository\LatinLanguageRepository;

class LatinLanguageService
{
    public function __construct(protected LatinLanguageRepository $latinLanguageRepository)
    {
    }

    public function checkLanguage($word): bool
    {
        $result = $this->latinLanguageRepository->findByName($word);

        if ($result) {
            return true;
        }

        return false;
    }

}