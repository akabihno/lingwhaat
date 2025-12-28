<?php

namespace App\Repository;

use App\Entity\VietnameseLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class VietnameseLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VietnameseLanguageEntity::class);
    }

}
