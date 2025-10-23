<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\AbstractLanguageRepository;
use App\Repository\TurkishLanguageRepository;

class TurkishLanguageService extends AbstractLanguageService
{
    public function __construct(protected TurkishLanguageRepository $turkishLanguageRepository)
    {
    }

    protected function getRepository(): AbstractLanguageRepository
    {
        return $this->turkishLanguageRepository;
    }
}