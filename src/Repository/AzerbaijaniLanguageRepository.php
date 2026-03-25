<?php

namespace App\Repository;

use App\Entity\AzerbaijaniLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class AzerbaijaniLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AzerbaijaniLanguageEntity::class);
    }

}
