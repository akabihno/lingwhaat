<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\AbstractLanguageRepository;
use App\Repository\HindiLanguageRepository;

class HindiLanguageService extends AbstractLanguageService
{
    public function __construct(protected HindiLanguageRepository $hindiLanguageRepository)
    {
    }

    public function checkLanguage($word): bool
    {
        return (bool) $this->hindiLanguageRepository->findByName($word);
    }

    public function fetchAllNamesAndIpa(): array
    {
        return $this->hindiLanguageRepository->findAllNamesAndIpa();
    }

    protected function getRepository(): AbstractLanguageRepository
    {
        return $this->hindiLanguageRepository;
    }
}