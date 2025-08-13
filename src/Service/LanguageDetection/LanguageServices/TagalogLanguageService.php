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
        return (bool) $this->tagalogLanguageRepository->findByName($word);
    }

    public function fetchAllNamesAndIpa(): array
    {
        return $this->tagalogLanguageRepository->findAllNamesAndIpa();
    }

}