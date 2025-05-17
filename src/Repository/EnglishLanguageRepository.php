<?php

namespace App\Repository;

use App\Entity\EnglishLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class EnglishLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EnglishLanguageEntity::class);
    }

}