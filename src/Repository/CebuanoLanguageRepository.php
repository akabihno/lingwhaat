<?php

namespace App\Repository;

use App\Entity\CebuanoLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class CebuanoLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CebuanoLanguageEntity::class);
    }

}
