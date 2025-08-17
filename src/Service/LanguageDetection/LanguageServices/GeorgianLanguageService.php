<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\AbstractLanguageRepository;
use App\Repository\GeorgianLanguageRepository;

class GeorgianLanguageService extends AbstractLanguageService
{
    public function __construct(protected GeorgianLanguageRepository $georgianLanguageRepository)
    {
    }

    public function checkLanguage($word): bool
    {
        return (bool) $this->georgianLanguageRepository->findByName($word);
    }

    public function fetchAllNamesAndIpa(): array
    {
        return $this->georgianLanguageRepository->findAllNamesAndIpa();
    }

    protected function getRepository(): AbstractLanguageRepository
    {
        return $this->georgianLanguageRepository;
    }
}