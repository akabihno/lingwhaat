<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\AbstractLanguageRepository;
use App\Repository\SwedishLanguageRepository;

class SwedishLanguageService extends AbstractLanguageService
{
    public function __construct(protected SwedishLanguageRepository $swedishLanguageRepository)
    {
    }

    public function checkLanguage($word): bool
    {
        return (bool) $this->swedishLanguageRepository->findByName($word);
    }

    protected function getRepository(): AbstractLanguageRepository
    {
        return $this->swedishLanguageRepository;
    }
}