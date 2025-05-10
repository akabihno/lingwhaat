<?php

namespace App\Repository;

use App\Entity\SpanishLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class SpanishLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SpanishLanguageEntity::class);
    }

}