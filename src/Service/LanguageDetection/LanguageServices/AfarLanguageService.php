<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\AbstractLanguageRepository;
use App\Repository\AfarLanguageRepository;

class AfarLanguageService extends AbstractLanguageService
{
    public function __construct(protected AfarLanguageRepository $afarLanguageRepository)
    {
    }

    protected function getRepository(): AbstractLanguageRepository
    {
        return $this->afarLanguageRepository;
    }

}