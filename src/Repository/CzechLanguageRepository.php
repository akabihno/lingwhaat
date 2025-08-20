<?php

namespace App\Repository;

use App\Entity\CzechLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class CzechLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CzechLanguageEntity::class);
    }

}