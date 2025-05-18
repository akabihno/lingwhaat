<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\EstonianLanguageRepository;

class EstonianLanguageService
{
    public function __construct(protected EstonianLanguageRepository $estonianLanguageRepository)
    {
    }

    public function checkLanguage($word): bool
    {
        $result = $this->estonianLanguageRepository->findByName($word);

        if ($result) {
            return true;
        }

        return false;
    }

}