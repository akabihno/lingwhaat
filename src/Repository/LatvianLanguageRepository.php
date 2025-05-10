<?php

namespace App\Repository;

use App\Entity\LatvianLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class LatvianLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LatvianLanguageEntity::class);
    }

}