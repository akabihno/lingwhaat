<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\PortugueseLanguageRepository;

class PortugueseLanguageService
{
    public function __construct(protected PortugueseLanguageRepository $portugueseLanguageRepository)
    {
    }

    public function checkLanguage($word): bool
    {
        $result = $this->portugueseLanguageRepository->findByName($word);

        if ($result) {
            return true;
        }

        return false;
    }

}