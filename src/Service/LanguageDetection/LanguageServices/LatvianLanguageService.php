<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\AbstractLanguageRepository;
use App\Repository\LatvianLanguageRepository;

class LatvianLanguageService extends AbstractLanguageService
{
    public function __construct(protected LatvianLanguageRepository $latvianLanguageRepository)
    {
    }

    protected function getRepository(): AbstractLanguageRepository
    {
        return $this->latvianLanguageRepository;
    }
}