<?php

namespace App\Repository;

use App\Entity\SlovakLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class SlovakLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SlovakLanguageEntity::class);
    }

}
