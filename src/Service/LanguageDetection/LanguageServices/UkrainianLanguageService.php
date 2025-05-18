<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\UkrainianLanguageRepository;

class UkrainianLanguageService
{
    public function __construct(protected UkrainianLanguageRepository $ukrainianLanguageRepository)
    {
    }

    public function checkLanguage($word): bool
    {
        $result = $this->ukrainianLanguageRepository->findByName($word);

        if ($result) {
            return true;
        }

        return false;
    }

}