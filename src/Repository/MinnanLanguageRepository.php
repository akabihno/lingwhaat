<?php

namespace App\Repository;

use App\Entity\MinnanLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class MinnanLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MinnanLanguageEntity::class);
    }

}
