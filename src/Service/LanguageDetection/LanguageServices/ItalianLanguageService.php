<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\ItalianLanguageRepository;

class ItalianLanguageService
{
    public function __construct(protected ItalianLanguageRepository $italianLanguageRepository)
    {
    }

    public function checkLanguage($word): bool
    {
        $result = $this->italianLanguageRepository->findByName($word);

        if ($result) {
            return true;
        }

        return false;
    }

}