<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\AbstractLanguageRepository;
use App\Repository\BretonLanguageRepository;

class BretonLanguageService extends AbstractLanguageService
{
    public function __construct(protected BretonLanguageRepository $bretonLanguageRepository)
    {
    }

    protected function getRepository(): AbstractLanguageRepository
    {
        return $this->bretonLanguageRepository;
    }

}