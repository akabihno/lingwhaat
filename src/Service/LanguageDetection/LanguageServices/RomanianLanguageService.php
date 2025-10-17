<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\AbstractLanguageRepository;
use App\Repository\RomanianLanguageRepository;

class RomanianLanguageService extends AbstractLanguageService
{
    public function __construct(protected RomanianLanguageRepository $romanianLanguageRepository)
    {
    }

    public function checkLanguage($word): bool
    {
        return (bool) $this->romanianLanguageRepository->findByName($word);
    }

    protected function getRepository(): AbstractLanguageRepository
    {
        return $this->romanianLanguageRepository;
    }
}