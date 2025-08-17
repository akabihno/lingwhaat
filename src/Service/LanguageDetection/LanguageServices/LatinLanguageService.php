<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\LatinLanguageRepository;

class LatinLanguageService extends AbstractLanguageService
{
    public function __construct(protected LatinLanguageRepository $latinLanguageRepository)
    {
    }

    public function checkLanguage($word): bool
    {
        return (bool) $this->latinLanguageRepository->findByName($word);
    }

    public function fetchAllNamesAndIpa(): array
    {
        return $this->latinLanguageRepository->findAllNamesAndIpa();
    }

}