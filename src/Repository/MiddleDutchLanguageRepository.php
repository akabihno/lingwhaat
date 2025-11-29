<?php

namespace App\Repository;

use App\Entity\MiddleDutchLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class MiddleDutchLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MiddleDutchLanguageEntity::class);
    }

}