<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\AbstractLanguageRepository;
use App\Repository\EstonianLanguageRepository;

class EstonianLanguageService extends AbstractLanguageService
{
    public function __construct(protected EstonianLanguageRepository $estonianLanguageRepository)
    {
    }

    public function checkLanguage($word): bool
    {
        return (bool) $this->estonianLanguageRepository->findByName($word);
    }

    protected function getRepository(): AbstractLanguageRepository
    {
        return $this->estonianLanguageRepository;
    }
}