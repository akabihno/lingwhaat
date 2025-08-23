<?php

namespace App\Repository;

use App\Entity\ArmenianLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class ArmenianLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ArmenianLanguageEntity::class);
    }

}