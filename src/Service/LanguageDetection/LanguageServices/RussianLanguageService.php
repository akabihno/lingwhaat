<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\RussianLanguageRepository;

class RussianLanguageService
{
    public function __construct(protected RussianLanguageRepository $russianLanguageRepository)
    {
    }

    public function checkLanguage($word): bool
    {
        $result = $this->russianLanguageRepository->findByName($word);

        if ($result) {
            return true;
        }

        return false;
    }

}