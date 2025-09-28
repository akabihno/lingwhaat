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

    public function findStartingByName(string $name): bool
    {
        $repository = $this->getRepository();

        return $repository->findStartingByName($name);
    }

    public function fetchAllNames(): array
    {
        $repository = $this->getRepository();

        return $repository->findAllNames();
    }

    public function fetchOneByName(string $word)
    {
        $repository = $this->getRepository();

        return $repository->findOneBy(['name' => $word]);
    }

    public function fetchAllNamesWithoutUniquePatternCheck(int $limit): array
    {
        $repository = $this->getRepository();

        return $repository->findAllNamesWithoutUniquePatternCheck($limit);
    }

    protected function modifyName(string $word): string
    {
        return substr($word, 1, -1);
    }
}