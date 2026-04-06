<?php

namespace App\Repository\ManuscriptMatch;

use App\Entity\ManuscriptMatch\ManuscriptPatternMatchEntity;
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
     * @return \App\Entity\ManuscriptMatch\ManuscriptPatternMatchEntity[]
     */
    public function findBySourceId(int $sourceId): array
    {
        return $this->findBy(['sourceId' => $sourceId]);
    }

}