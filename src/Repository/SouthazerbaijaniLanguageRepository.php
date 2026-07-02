<?php

namespace App\Repository;

use App\Entity\SouthazerbaijaniLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class SouthazerbaijaniLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SouthazerbaijaniLanguageEntity::class);
    }

}
