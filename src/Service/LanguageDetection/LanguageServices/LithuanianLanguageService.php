<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\LithuanianLanguageRepository;

class LithuanianLanguageService
{
    public function __construct(protected LithuanianLanguageRepository $lithuanianLanguageRepository)
    {
    }

    public function checkLanguage($word): bool
    {
        $result = $this->lithuanianLanguageRepository->findByName($word);

        if ($result) {
            return true;
        }

        return false;
    }

}