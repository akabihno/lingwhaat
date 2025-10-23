<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\AbstractLanguageRepository;
use App\Repository\ArmenianLanguageRepository;

class ArmenianLanguageService extends AbstractLanguageService
{
    public function __construct(protected ArmenianLanguageRepository $armenianLanguageRepository)
    {
    }

    protected function getRepository(): AbstractLanguageRepository
    {
        return $this->armenianLanguageRepository;
    }

}