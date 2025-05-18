<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\SerboCroatianLanguageRepository;

class SerboCroatianLanguageService
{
    public function __construct(protected SerboCroatianLanguageRepository $serboCroatianLanguageRepository)
    {
    }

    public function checkLanguage($word): bool
    {
        $result = $this->serboCroatianLanguageRepository->findByName($word);

        if ($result) {
            return true;
        }

        return false;
    }

}