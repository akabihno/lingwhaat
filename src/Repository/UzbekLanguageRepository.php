<?php

namespace App\Repository;

use App\Entity\UzbekLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class UzbekLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UzbekLanguageEntity::class);
    }

}