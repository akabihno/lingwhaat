<?php

namespace App\Repository;

use App\Entity\EstonianLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class EstonianLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EstonianLanguageEntity::class);
    }

}