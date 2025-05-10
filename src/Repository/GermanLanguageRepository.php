<?php

namespace App\Repository;

use App\Entity\GermanLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class GermanLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GermanLanguageEntity::class);
    }

}