<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\TurkishLanguageRepository;

class TurkishLanguageService extends AbstractLanguageService
{
    public function __construct(protected TurkishLanguageRepository $turkishLanguageRepository)
    {
    }

    public function checkLanguage($word): bool
    {
        return (bool) $this->turkishLanguageRepository->findByName($word);
    }

    public function fetchAllNamesAndIpa(): array
    {
        return $this->turkishLanguageRepository->findAllNamesAndIpa();
    }

}