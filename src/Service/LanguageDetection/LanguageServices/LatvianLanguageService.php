<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\AbstractLanguageRepository;
use App\Repository\LatvianLanguageRepository;

class LatvianLanguageService extends AbstractLanguageService
{
    public function __construct(protected LatvianLanguageRepository $latvianLanguageRepository)
    {
    }

    public function checkLanguage($word): bool
    {
        return (bool) $this->latvianLanguageRepository->findByName($word);
    }

    protected function getRepository(): AbstractLanguageRepository
    {
        return $this->latvianLanguageRepository;
    }
}