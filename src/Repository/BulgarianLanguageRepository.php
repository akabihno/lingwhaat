<?php

namespace App\Repository;

use App\Entity\BulgarianLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class BulgarianLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BulgarianLanguageEntity::class);
    }

}
