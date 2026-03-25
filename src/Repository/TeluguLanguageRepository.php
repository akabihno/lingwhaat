<?php

namespace App\Repository;

use App\Entity\TeluguLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class TeluguLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TeluguLanguageEntity::class);
    }

}
