<?php

namespace App\Repository;

use App\Entity\AlbanianLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class AlbanianLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AlbanianLanguageEntity::class);
    }

}