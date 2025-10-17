<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\AbstractLanguageRepository;
use App\Repository\FrenchLanguageRepository;

class FrenchLanguageService extends AbstractLanguageService
{
    public function __construct(protected FrenchLanguageRepository $frenchLanguageRepository)
    {
    }

    public function checkLanguage($word): bool
    {
        return (bool) $this->frenchLanguageRepository->findByName($word);
    }

    protected function getRepository(): AbstractLanguageRepository
    {
        return $this->frenchLanguageRepository;
    }
}