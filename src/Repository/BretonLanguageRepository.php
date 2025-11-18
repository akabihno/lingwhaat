<?php

namespace App\Repository;

use App\Entity\BretonLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class BretonLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BretonLanguageEntity::class);
    }

}