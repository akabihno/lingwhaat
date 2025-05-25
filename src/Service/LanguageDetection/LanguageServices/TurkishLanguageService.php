<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\TurkishLanguageRepository;

class TurkishLanguageService
{
    public function __construct(protected TurkishLanguageRepository $turkishLanguageRepository)
    {
    }

    public function checkLanguage($word): bool
    {
        $result = $this->turkishLanguageRepository->findByName($word);

        if ($result) {
            return true;
        }

        return false;
    }

}