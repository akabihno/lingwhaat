<?php

namespace App\Repository;

use App\Entity\FrenchLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class FrenchLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FrenchLanguageEntity::class);
    }

}