<?php

namespace App\Repository;

use App\Entity\DanishLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class DanishLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DanishLanguageEntity::class);
    }

}
