<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\AbstractLanguageRepository;

abstract class AbstractLanguageService
{
    protected AbstractLanguageRepository $repository;

    public function findApproximateByName(string $name): bool
    {

        return $this->repository->findApproximateByName($this->modifyName($name));
    }

    protected function modifyName(string $word): string
    {
        return substr($word, 1, -1);
    }
}