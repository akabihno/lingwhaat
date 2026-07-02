<?php

namespace App\Repository;

use App\Entity\MinangkabauLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class MinangkabauLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MinangkabauLanguageEntity::class);
    }

}
