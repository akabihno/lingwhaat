<?php

namespace App\Repository;

use App\Entity\BengaliLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class BengaliLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BengaliLanguageEntity::class);
    }

}