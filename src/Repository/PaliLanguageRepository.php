<?php

namespace App\Repository;

use App\Entity\PaliLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class PaliLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaliLanguageEntity::class);
    }

}
