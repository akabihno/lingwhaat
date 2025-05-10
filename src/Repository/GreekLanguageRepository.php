<?php

namespace App\Repository;

use App\Entity\GreekLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class GreekLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GreekLanguageEntity::class);
    }

}