<?php

namespace App\Repository;

use App\Entity\HebrewLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class HebrewLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HebrewLanguageEntity::class);
    }

}
