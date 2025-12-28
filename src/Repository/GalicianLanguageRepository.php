<?php

namespace App\Repository;

use App\Entity\GalicianLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class GalicianLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GalicianLanguageEntity::class);
    }

}
