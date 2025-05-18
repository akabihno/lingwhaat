<?php

namespace App\Repository;

use App\Entity\HindiLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class HindiLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HindiLanguageEntity::class);
    }

}