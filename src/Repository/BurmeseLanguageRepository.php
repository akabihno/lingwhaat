<?php

namespace App\Repository;

use App\Entity\BurmeseLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class BurmeseLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BurmeseLanguageEntity::class);
    }

}
