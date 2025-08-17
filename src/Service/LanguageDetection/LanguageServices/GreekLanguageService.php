<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\GreekLanguageRepository;

class GreekLanguageService extends AbstractLanguageService
{
    public function __construct(protected GreekLanguageRepository $greekLanguageRepository)
    {
    }

    public function checkLanguage($word): bool
    {
        return (bool) $this->greekLanguageRepository->findByName($word);
    }

    public function fetchAllNamesAndIpa(): array
    {
        return $this->greekLanguageRepository->findAllNamesAndIpa();
    }

}