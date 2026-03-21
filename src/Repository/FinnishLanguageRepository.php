<?php

namespace App\Repository;

use App\Entity\FinnishLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class FinnishLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FinnishLanguageEntity::class);
    }

}
