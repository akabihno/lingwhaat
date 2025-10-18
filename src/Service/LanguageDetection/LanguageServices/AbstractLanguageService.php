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

    public function fetchAllNamesAndIpa(int $limit = AbstractLanguageRepository::PRONUNCIATION_MAX_RESULTS, int $offset = 0): array
    {
        $repository = $this->getRepository();

        return $repository->findAllNamesAndIpa($limit, $offset);
    }

    public function fetchAllNamesWithoutUniquePatternCheck(int $limit): array
    {
        $repository = $this->getRepository();

        return $repository->findAllNamesWithoutUniquePatternCheck($limit);
    }

    public function fetchAllEntitiesWithIpa(int $limit, int $offset): array
    {
        $repository = $this->getRepository();

        return $repository->findAllEntitiesWithIpa($limit, $offset);
    }

    protected function modifyName(string $word): string
    {
        return substr($word, 1, -1);
    }
}