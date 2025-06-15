<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\GreekLanguageRepository;

class GreekLanguageService
{
    public function __construct(protected GreekLanguageRepository $greekLanguageRepository)
    {
    }

    public function checkLanguage($word): bool
    {
        $result = $this->greekLanguageRepository->findByName($word);

        if ($result) {
            return true;
        }

        return false;
    }

    public function fetchAllNamesAndIpa(): array
    {
        return $this->greekLanguageRepository->findAllNamesAndIpa();
    }

}