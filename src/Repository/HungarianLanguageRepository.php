<?php

namespace App\Repository;

use App\Entity\HungarianLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class HungarianLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HungarianLanguageEntity::class);
    }

}
