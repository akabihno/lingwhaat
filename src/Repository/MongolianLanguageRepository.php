<?php

namespace App\Repository;

use App\Entity\MongolianLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class MongolianLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MongolianLanguageEntity::class);
    }

}
