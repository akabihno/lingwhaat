<?php

namespace App\Repository;

use App\Entity\UrduLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class UrduLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UrduLanguageEntity::class);
    }

}
