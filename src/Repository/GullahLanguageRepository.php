<?php

namespace App\Repository;

use App\Entity\GullahLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class GullahLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GullahLanguageEntity::class);
    }

}
