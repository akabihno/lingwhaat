<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\AbstractLanguageRepository;
use App\Repository\AfrikaansLanguageRepository;

class AfrikaansLanguageService extends AbstractLanguageService
{
    public function __construct(protected AfrikaansLanguageRepository $afrikaansLanguageRepository)
    {
    }

    public function checkLanguage($word): bool
    {
        return (bool) $this->afrikaansLanguageRepository->findByName($word);
    }

    public function fetchAllNamesAndIpa(): array
    {
        return $this->afrikaansLanguageRepository->findAllNamesAndIpa();
    }

    protected function getRepository(): AbstractLanguageRepository
    {
        return $this->afrikaansLanguageRepository;
    }

}