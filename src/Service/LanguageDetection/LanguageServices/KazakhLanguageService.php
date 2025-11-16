<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\AbstractLanguageRepository;
use App\Repository\KazakhLanguageRepository;

class KazakhLanguageService extends AbstractLanguageService
{
    public function __construct(protected KazakhLanguageRepository $kazakhLanguageRepository)
    {
    }

    protected function getRepository(): AbstractLanguageRepository
    {
        return $this->kazakhLanguageRepository;
    }

}