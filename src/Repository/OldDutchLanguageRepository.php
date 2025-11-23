<?php

namespace App\Repository;

use App\Entity\OldDutchLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class OldDutchLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OldDutchLanguageEntity::class);
    }

}