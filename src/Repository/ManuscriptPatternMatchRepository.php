<?php

namespace App\Repository;

use App\Entity\ManuscriptPatternMatchEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ManuscriptPatternMatchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
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