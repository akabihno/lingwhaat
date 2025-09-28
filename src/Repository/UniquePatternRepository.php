<?php

namespace App\Repository;

use App\Entity\UniquePatternEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Dotenv\Dotenv;

class UniquePatternRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        $dotenv = new Dotenv();
        $dotenv->loadEnv(dirname(__DIR__, 2).'/.env');

        parent::__construct($registry, UniquePatternEntity::class);
    }

    public function add(UniquePatternEntity $uniquePattern): void
    {
        $entityManager = $this->getEntityManager();

        $entityManager->persist($uniquePattern);
        $entityManager->flush();
    }



}