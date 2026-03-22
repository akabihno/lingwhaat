<?php

namespace App\Repository;

use App\Entity\PersianLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class PersianLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PersianLanguageEntity::class);
    }

}
