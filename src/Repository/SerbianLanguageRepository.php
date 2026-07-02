<?php

namespace App\Repository;

use App\Entity\SerbianLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class SerbianLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SerbianLanguageEntity::class);
    }

}
