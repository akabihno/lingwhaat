<?php

namespace App\Repository;

use App\Entity\KomiLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class KomiLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KomiLanguageEntity::class);
    }

}
