<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\AbstractLanguageRepository;
use App\Repository\SwedishLanguageRepository;

class SwedishLanguageService extends AbstractLanguageService
{
    public function __construct(protected SwedishLanguageRepository $swedishLanguageRepository)
    {
    }

    protected function getRepository(): AbstractLanguageRepository
    {
        return $this->swedishLanguageRepository;
    }
}