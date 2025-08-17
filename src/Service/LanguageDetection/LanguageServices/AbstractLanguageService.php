<?php

namespace App\Service\LanguageDetection\LanguageServices;

use App\Repository\AbstractLanguageRepository;

abstract class AbstractLanguageService
{
    abstract protected function getRepository(): AbstractLanguageRepository;

    public function findApproximateByName(string $name): bool
    {
        $repository = $this->getRepository();

        return $repository->findApproximateByName($name);
    }

    protected function modifyName(string $word): string
    {
        return substr($word, 1, -1);
    }
}