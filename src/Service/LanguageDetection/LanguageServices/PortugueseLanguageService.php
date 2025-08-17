<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\PortugueseLanguageRepository;

class PortugueseLanguageService extends AbstractLanguageService
{
    public function __construct(protected PortugueseLanguageRepository $portugueseLanguageRepository)
    {
    }

    public function checkLanguage($word): bool
    {
        return (bool) $this->portugueseLanguageRepository->findByName($word);
    }

    public function fetchAllNamesAndIpa(): array
    {
        return $this->portugueseLanguageRepository->findAllNamesAndIpa();
    }

}