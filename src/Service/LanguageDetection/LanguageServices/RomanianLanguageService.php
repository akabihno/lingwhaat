<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\AbstractLanguageRepository;
use App\Repository\RomanianLanguageRepository;

class RomanianLanguageService extends AbstractLanguageService
{
    public function __construct(protected RomanianLanguageRepository $romanianLanguageRepository)
    {
    }

    protected function getRepository(): AbstractLanguageRepository
    {
        return $this->romanianLanguageRepository;
    }
}