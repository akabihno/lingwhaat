<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\AbstractLanguageRepository;
use App\Repository\PortugueseLanguageRepository;

class PortugueseLanguageService extends AbstractLanguageService
{
    public function __construct(protected PortugueseLanguageRepository $portugueseLanguageRepository)
    {
    }

    protected function getRepository(): AbstractLanguageRepository
    {
        return $this->portugueseLanguageRepository;
    }
}