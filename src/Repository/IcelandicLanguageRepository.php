<?php

namespace App\Repository;

use App\Entity\IcelandicLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class IcelandicLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, IcelandicLanguageEntity::class);
    }

}
