<?php

namespace App\Repository;

use App\Entity\PolishLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class PolishLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PolishLanguageEntity::class);
    }

}