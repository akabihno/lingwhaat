<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\AbstractLanguageRepository;

abstract class AbstractLanguageService
{
    abstract protected function getRepository(): AbstractLanguageRepository;

    public function fetchAllNamesAndIpa(int $limit = AbstractLanguageRepository::PRONUNCIATION_MAX_RESULTS, int $offset = 0): array
    {
        $repository = $this->getRepository();

        return $repository->findAllNamesAndIpa($limit, $offset);
    }
}