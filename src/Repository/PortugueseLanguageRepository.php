<?php

namespace App\Repository;

use App\Entity\PortugueseLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class PortugueseLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PortugueseLanguageEntity::class);
    }

}