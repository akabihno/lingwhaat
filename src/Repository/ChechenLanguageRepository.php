<?php

namespace App\Repository;

use App\Entity\ChechenLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class ChechenLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChechenLanguageEntity::class);
    }

}
