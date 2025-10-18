<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\AbstractLanguageRepository;
use App\Repository\UkrainianLanguageRepository;

class UkrainianLanguageService extends AbstractLanguageService
{
    public function __construct(protected UkrainianLanguageRepository $ukrainianLanguageRepository)
    {
    }

    public function checkLanguage($word): bool
    {
        return (bool) $this->ukrainianLanguageRepository->findByName($word);
    }

    protected function getRepository(): AbstractLanguageRepository
    {
        return $this->ukrainianLanguageRepository;
    }
}