<?php

namespace App\Repository;

use App\Entity\AfrikaansLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class AfrikaansLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AfrikaansLanguageEntity::class);
    }

}