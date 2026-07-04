<?php

namespace App\Repository;

use App\Entity\MalayLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class MalayLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MalayLanguageEntity::class);
    }

}
