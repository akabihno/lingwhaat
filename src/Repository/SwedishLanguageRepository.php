<?php

namespace App\Repository;

use App\Entity\SwedishLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class SwedishLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SwedishLanguageEntity::class);
    }

}