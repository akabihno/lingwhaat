<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\AbstractLanguageRepository;
use App\Repository\GeorgianLanguageRepository;

class GeorgianLanguageService extends AbstractLanguageService
{
    public function __construct(protected GeorgianLanguageRepository $georgianLanguageRepository)
    {
    }

    protected function getRepository(): AbstractLanguageRepository
    {
        return $this->georgianLanguageRepository;
    }
}