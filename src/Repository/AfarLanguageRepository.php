<?php

namespace App\Repository;

use App\Entity\AfarLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class AfarLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AfarLanguageEntity::class);
    }

}