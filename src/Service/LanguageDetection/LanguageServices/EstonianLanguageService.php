<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\AbstractLanguageRepository;
use App\Repository\EstonianLanguageRepository;

class EstonianLanguageService extends AbstractLanguageService
{
    public function __construct(protected EstonianLanguageRepository $estonianLanguageRepository)
    {
    }

    protected function getRepository(): AbstractLanguageRepository
    {
        return $this->estonianLanguageRepository;
    }
}