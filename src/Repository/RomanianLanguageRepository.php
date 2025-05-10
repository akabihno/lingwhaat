<?php

namespace App\Repository;

use App\Entity\RomanianLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class RomanianLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RomanianLanguageEntity::class);
    }

}