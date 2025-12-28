<?php

namespace App\Repository;

use App\Entity\MandarinLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class MandarinLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MandarinLanguageEntity::class);
    }

}
