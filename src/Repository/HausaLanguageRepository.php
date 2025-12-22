<?php

namespace App\Repository;

use App\Entity\HausaLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class HausaLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HausaLanguageEntity::class);
    }

}
