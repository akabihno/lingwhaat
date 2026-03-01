<?php

namespace App\Repository;

use App\Entity\CatalanLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class CatalanLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CatalanLanguageEntity::class);
    }

}
