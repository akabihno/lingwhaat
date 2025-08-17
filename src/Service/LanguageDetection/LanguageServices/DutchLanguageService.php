<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\AbstractLanguageRepository;
use App\Repository\DutchLanguageRepository;

class DutchLanguageService extends AbstractLanguageService
{
    public function __construct(protected DutchLanguageRepository $dutchLanguageRepository)
    {
    }

    public function checkLanguage($word): bool
    {
        return (bool) $this->dutchLanguageRepository->findByName($word);
    }

    public function fetchAllNamesAndIpa(): array
    {
        return $this->dutchLanguageRepository->findAllNamesAndIpa();
    }

    protected function getRepository(): AbstractLanguageRepository
    {
        return $this->dutchLanguageRepository;
    }
}