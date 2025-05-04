<?php

namespace App\Service\LanguageDetection;

use App\Repository\GermanLanguageRepository;

class GermanLanguageService
{
    public function __construct(protected GermanLanguageRepository $germanLanguageRepository)
    {
    }

    public function checkLanguage($word): bool
    {
        $result = $this->germanLanguageRepository->findByName($word);

        if ($result) {
            return true;
        }

        return false;
    }


}