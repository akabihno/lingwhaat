<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\SerboCroatianLanguageRepository;

class SerboCroatianLanguageService
{
    public function __construct(protected SerboCroatianLanguageRepository $serboCroatianLanguageRepository)
    {
    }

    public function checkLanguage($word): bool
    {
        return (bool) $this->serboCroatianLanguageRepository->findByName($word);
    }

    public function fetchAllNamesAndIpa(): array
    {
        return $this->serboCroatianLanguageRepository->findAllNamesAndIpa();
    }

}