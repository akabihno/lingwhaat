<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\AbstractLanguageRepository;
use App\Repository\RussianLanguageRepository;

class RussianLanguageService extends AbstractLanguageService
{
    public function __construct(protected RussianLanguageRepository $russianLanguageRepository)
    {
    }

    protected function getRepository(): AbstractLanguageRepository
    {
        return $this->russianLanguageRepository;
    }
}