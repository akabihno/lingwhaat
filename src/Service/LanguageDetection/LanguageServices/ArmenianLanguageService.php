<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\AbstractLanguageRepository;
use App\Repository\ArmenianLanguageRepository;

class ArmenianLanguageService extends AbstractLanguageService
{
    public function __construct(protected ArmenianLanguageRepository $armenianLanguageRepository)
    {
    }

    public function checkLanguage($word): bool
    {
        return (bool) $this->armenianLanguageRepository->findByName($word);
    }

    protected function getRepository(): AbstractLanguageRepository
    {
        return $this->armenianLanguageRepository;
    }

}