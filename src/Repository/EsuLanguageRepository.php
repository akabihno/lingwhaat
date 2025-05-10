<?php

namespace App\Repository;

use App\Entity\EsuLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class EsuLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EsuLanguageEntity::class);
    }

}