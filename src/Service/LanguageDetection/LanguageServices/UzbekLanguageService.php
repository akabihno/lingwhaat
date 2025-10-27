<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\AbstractLanguageRepository;
use App\Repository\UzbekLanguageRepository;

class UzbekLanguageService extends AbstractLanguageService
{
    public function __construct(protected UzbekLanguageRepository $uzbekLanguageRepository)
    {
    }

    protected function getRepository(): AbstractLanguageRepository
    {
        return $this->uzbekLanguageRepository;
    }

}