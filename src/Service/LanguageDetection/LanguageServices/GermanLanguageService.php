<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\AbstractLanguageRepository;
use App\Repository\GermanLanguageRepository;

class GermanLanguageService extends AbstractLanguageService
{
    public function __construct(protected GermanLanguageRepository $germanLanguageRepository)
    {
    }


    protected function getRepository(): AbstractLanguageRepository
    {
        return $this->germanLanguageRepository;
    }
}