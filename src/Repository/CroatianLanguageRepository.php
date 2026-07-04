<?php

namespace App\Repository;

use App\Entity\CroatianLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class CroatianLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CroatianLanguageEntity::class);
    }

}
