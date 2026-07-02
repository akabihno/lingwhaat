<?php

namespace App\Repository;

use App\Entity\EsperantoLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class EsperantoLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EsperantoLanguageEntity::class);
    }

}
