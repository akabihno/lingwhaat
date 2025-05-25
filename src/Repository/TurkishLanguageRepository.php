<?php

namespace App\Repository;

use App\Entity\TurkishLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class TurkishLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TurkishLanguageEntity::class);
    }

}