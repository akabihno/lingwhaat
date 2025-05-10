<?php

namespace App\Repository;

use App\Entity\LatinLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class LatinLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LatinLanguageEntity::class);
    }

}