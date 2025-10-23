<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\AbstractLanguageRepository;
use App\Repository\CzechLanguageRepository;

class CzechLanguageService extends AbstractLanguageService
{
    public function __construct(protected CzechLanguageRepository $czechLanguageRepository)
    {
    }

    protected function getRepository(): AbstractLanguageRepository
    {
        return $this->czechLanguageRepository;
    }

}