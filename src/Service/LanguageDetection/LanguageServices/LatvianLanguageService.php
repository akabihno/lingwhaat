<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\LatvianLanguageRepository;

class LatvianLanguageService
{
    public function __construct(protected LatvianLanguageRepository $latvianLanguageRepository)
    {
    }

    public function checkLanguage($word): bool
    {
        $result = $this->latvianLanguageRepository->findByName($word);

        if ($result) {
            return true;
        }

        return false;
    }

    public function fetchAllNamesAndIpa(): array
    {
        return $this->latvianLanguageRepository->findAllNamesAndIpa();
    }

}