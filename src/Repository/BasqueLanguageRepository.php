<?php

namespace App\Repository;

use App\Entity\BasqueLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class BasqueLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BasqueLanguageEntity::class);
    }

}
