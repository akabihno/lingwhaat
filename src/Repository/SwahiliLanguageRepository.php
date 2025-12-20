<?php

namespace App\Repository;

use App\Entity\SwahiliLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class SwahiliLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SwahiliLanguageEntity::class);
    }

}
