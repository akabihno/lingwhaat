<?php

namespace App\Repository;

use App\Entity\RussianLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class RussianLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RussianLanguageEntity::class);
    }

}