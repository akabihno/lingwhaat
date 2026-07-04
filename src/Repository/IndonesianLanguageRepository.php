<?php

namespace App\Repository;

use App\Entity\IndonesianLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class IndonesianLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, IndonesianLanguageEntity::class);
    }

}
