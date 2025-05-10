<?php

namespace App\Repository;

use App\Entity\UkrainianLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class UkrainianLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UkrainianLanguageEntity::class);
    }

}