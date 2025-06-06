<?php

namespace App\Repository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Dotenv\Dotenv;

abstract class AbstractLanguageRepository extends ServiceEntityRepository
{
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

}