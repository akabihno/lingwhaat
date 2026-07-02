<?php

namespace App\Repository;

use App\Entity\WelshLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class WelshLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WelshLanguageEntity::class);
    }

}
