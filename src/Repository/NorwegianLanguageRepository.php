<?php

namespace App\Repository;

use App\Entity\NorwegianLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class NorwegianLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NorwegianLanguageEntity::class);
    }

}
