<?php

namespace App\Repository;

use App\Entity\SerboCroatianLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class SerboCroatianLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SerboCroatianLanguageEntity::class);
    }

}