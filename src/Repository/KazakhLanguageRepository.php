<?php

namespace App\Repository;

use App\Entity\KazakhLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class KazakhLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KazakhLanguageEntity::class);
    }

}