<?php

namespace App\Repository;

use App\Entity\GeorgianLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class GeorgianLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GeorgianLanguageEntity::class);
    }

}