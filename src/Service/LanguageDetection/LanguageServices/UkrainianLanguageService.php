<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\AbstractLanguageRepository;
use App\Repository\UkrainianLanguageRepository;

class UkrainianLanguageService extends AbstractLanguageService
{
    public function __construct(protected UkrainianLanguageRepository $ukrainianLanguageRepository)
    {
    }

    protected function getRepository(): AbstractLanguageRepository
    {
        return $this->ukrainianLanguageRepository;
    }
}