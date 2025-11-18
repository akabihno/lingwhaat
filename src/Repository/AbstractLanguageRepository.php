<?php

namespace App\Repository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Dotenv\Dotenv;

abstract class AbstractLanguageRepository extends ServiceEntityRepository
{
    public const int PRONUNCIATION_MAX_RESULTS = 50000;
    public function __construct(ManagerRegistry $registry, $entityClass)
    {
        $dotenv = new Dotenv();
        $dotenv->loadEnv(dirname(__DIR__, 2).'/.env');

        parent::__construct($registry, $entityClass);
    }

    public function findAllNamesAndIpa(int $limit = self::PRONUNCIATION_MAX_RESULTS, int $offset = 0): array
    {
        return $this->createQueryBuilder('e')
            ->select('e.name', 'e.ipa')
            ->where('e.ipa != :na')
            ->setParameter('na', 'Not available')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }

}