<?php

namespace App\Repository;

use App\Entity\WolofLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class WolofLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WolofLanguageEntity::class);
    }

}
