<?php

namespace App\Repository;

use App\Entity\SomaliLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class SomaliLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SomaliLanguageEntity::class);
    }

}
