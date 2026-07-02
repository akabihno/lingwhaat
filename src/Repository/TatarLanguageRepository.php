<?php

namespace App\Repository;

use App\Entity\TatarLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class TatarLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TatarLanguageEntity::class);
    }

}
