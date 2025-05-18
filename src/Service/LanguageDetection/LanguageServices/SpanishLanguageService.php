<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\SpanishLanguageRepository;

class SpanishLanguageService
{
    public function __construct(protected SpanishLanguageRepository $spanishLanguageRepository)
    {
    }

    public function checkLanguage($word): bool
    {
        $result = $this->spanishLanguageRepository->findByName($word);

        if ($result) {
            return true;
        }

        return false;
    }

}