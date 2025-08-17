<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\EsuLanguageRepository;

class EsuLanguageService extends AbstractLanguageService
{
    public function __construct(protected EsuLanguageRepository $esuLanguageRepository)
    {
    }

    public function checkLanguage($word): bool
    {
        return (bool) $this->esuLanguageRepository->findByName($word);
    }

    public function fetchAllNamesAndIpa(): array
    {
        return $this->esuLanguageRepository->findAllNamesAndIpa();
    }

}