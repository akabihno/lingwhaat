<?php

namespace App\Repository;

use App\Entity\ManuscriptPatternMatchScheduleEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ManuscriptPatternMatchScheduleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ManuscriptPatternMatchScheduleEntity::class);
    }

    public function getAll(): array
    {
        return $this->findAll();
    }

}