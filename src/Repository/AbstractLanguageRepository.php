<?php

namespace App\Repository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Dotenv\Dotenv;

abstract class AbstractLanguageRepository extends ServiceEntityRepository
{
    const int PRONUNCIATION_MAX_RESULTS = 50000;
    public function __construct(ManagerRegistry $registry, $entityClass)
    {
        $dotenv = new Dotenv();
        $dotenv->loadEnv(dirname(__DIR__, 2).'/.env');

        parent::__construct($registry, $entityClass);
    }
    public function findByName($name)
    {
        $qb = $this->createQueryBuilder('e')
            ->where('e.name = :name')
            ->setParameter('name', $name);

        $query = $qb->getQuery();

        return $query->execute();
    }

    public function findApproximateByName(string $name): bool
    {
        $qb = $this->createQueryBuilder('e')
            ->where('e.name LIKE :name')
            ->setParameter('name', '%'.$name.'%');

        $query = $qb->getQuery();

        return (bool) $query->execute();
    }

    public function findStartingByName(string $name): bool
    {
        $qb = $this->createQueryBuilder('e')
            ->where('e.name LIKE :name')
            ->setParameter('name', $name.'%');

        $query = $qb->getQuery();

        return (bool) $query->execute();
    }

    public function findAllNamesAndIpa(int $limit = self::PRONUNCIATION_MAX_RESULTS): array
    {
        return $this->createQueryBuilder('e')
            ->select('e.name', 'e.ipa')
            ->where('e.ipa != :na')
            ->setParameter('na', 'Not available')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }

    public function findAllNames(): array
    {
        return $this->createQueryBuilder('e')
            ->select('e.name')
            ->getQuery()
            ->getArrayResult();
    }

    public function findAllNamesWithoutUniquePatternCheck(int $limit = self::PRONUNCIATION_MAX_RESULTS): array
    {
        return $this->createQueryBuilder('e')
            ->select('e.name')
            ->where('e.uniquePatternCheck >= :dt')
            ->setParameter('dt', '1970-01-01 00:00:01')
            ->orderBy('e.uniquePatternCheck', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }

}