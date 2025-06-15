<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\TagalogLanguageRepository;

class TagalogLanguageService
{
    public function __construct(protected TagalogLanguageRepository $tagalogLanguageRepository)
    {
    }

    public function checkLanguage($word): bool
    {
        $result = $this->tagalogLanguageRepository->findByName($word);

        if ($result) {
            return true;
        }

        return false;
    }

    public function fetchAllNamesAndIpa(): array
    {
        return $this->tagalogLanguageRepository->findAllNamesAndIpa();
    }

}