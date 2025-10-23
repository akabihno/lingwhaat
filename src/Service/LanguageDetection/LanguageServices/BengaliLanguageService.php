<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\AbstractLanguageRepository;
use App\Repository\BengaliLanguageRepository;

class BengaliLanguageService extends AbstractLanguageService
{
    public function __construct(protected BengaliLanguageRepository $bengaliLanguageRepository)
    {
    }

    protected function getRepository(): AbstractLanguageRepository
    {
        return $this->bengaliLanguageRepository;
    }

}