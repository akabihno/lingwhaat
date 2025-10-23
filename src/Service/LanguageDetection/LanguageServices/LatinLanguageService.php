<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\AbstractLanguageRepository;
use App\Repository\LatinLanguageRepository;

class LatinLanguageService extends AbstractLanguageService
{
    public function __construct(protected LatinLanguageRepository $latinLanguageRepository)
    {
    }

    protected function getRepository(): AbstractLanguageRepository
    {
        return $this->latinLanguageRepository;
    }
}