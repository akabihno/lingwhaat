<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\AbstractLanguageRepository;
use App\Repository\AfrikaansLanguageRepository;

class AfrikaansLanguageService extends AbstractLanguageService
{
    public function __construct(protected AfrikaansLanguageRepository $afrikaansLanguageRepository)
    {
    }

    protected function getRepository(): AbstractLanguageRepository
    {
        return $this->afrikaansLanguageRepository;
    }

}