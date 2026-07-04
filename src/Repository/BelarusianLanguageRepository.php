<?php

namespace App\Repository;

use App\Entity\BelarusianLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class BelarusianLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BelarusianLanguageEntity::class);
    }

}
