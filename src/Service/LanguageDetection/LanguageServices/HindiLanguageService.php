<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\AbstractLanguageRepository;
use App\Repository\HindiLanguageRepository;

class HindiLanguageService extends AbstractLanguageService
{
    public function __construct(protected HindiLanguageRepository $hindiLanguageRepository)
    {
    }

    protected function getRepository(): AbstractLanguageRepository
    {
        return $this->hindiLanguageRepository;
    }
}