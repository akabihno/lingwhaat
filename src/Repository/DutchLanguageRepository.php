<?php

namespace App\Repository;

use App\Entity\DutchLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class DutchLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DutchLanguageEntity::class);
    }

}