<?php

namespace App\Repository;

use App\Entity\ManuscriptPatternMatchEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Dotenv\Dotenv;

class ManuscriptPatternMatchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        $dotenv = new Dotenv();
        $dotenv->loadEnv(dirname(__DIR__, 2).'/.env');

        parent::__construct($registry, ManuscriptPatternMatchEntity::class);
    }

    public function getAll(): array
    {
        return $this->findAll();
    }

    /**
     * @return \App\Entity\ManuscriptPatternMatchEntity[]
     */
    public function findBySourceId(int $sourceId): array
    {
        return $this->findBy(['sourceId' => $sourceId]);
    }

}