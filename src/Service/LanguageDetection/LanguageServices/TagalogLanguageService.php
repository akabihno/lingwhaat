<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\AbstractLanguageRepository;
use App\Repository\TagalogLanguageRepository;

class TagalogLanguageService extends AbstractLanguageService
{
    public function __construct(protected TagalogLanguageRepository $tagalogLanguageRepository)
    {
    }

    protected function getRepository(): AbstractLanguageRepository
    {
        return $this->tagalogLanguageRepository;
    }
}