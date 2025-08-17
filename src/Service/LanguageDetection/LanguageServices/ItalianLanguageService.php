<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\AbstractLanguageRepository;
use App\Repository\ItalianLanguageRepository;

class ItalianLanguageService extends AbstractLanguageService
{
    public function __construct(protected ItalianLanguageRepository $italianLanguageRepository)
    {
    }

    public function checkLanguage($word): bool
    {
        return (bool) $this->italianLanguageRepository->findByName($word);
    }

    public function fetchAllNamesAndIpa(): array
    {
        return $this->italianLanguageRepository->findAllNamesAndIpa();
    }

    protected function getRepository(): AbstractLanguageRepository
    {
        return $this->italianLanguageRepository;
    }
}