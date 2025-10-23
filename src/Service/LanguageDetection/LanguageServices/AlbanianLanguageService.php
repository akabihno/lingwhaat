<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\AbstractLanguageRepository;
use App\Repository\AlbanianLanguageRepository;

class AlbanianLanguageService extends AbstractLanguageService
{
    public function __construct(protected AlbanianLanguageRepository $albanianLanguageRepository)
    {
    }

    protected function getRepository(): AbstractLanguageRepository
    {
        return $this->albanianLanguageRepository;
    }

}