<?php

namespace App\Repository;

use App\Entity\WarayLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class WarayLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WarayLanguageEntity::class);
    }

}
