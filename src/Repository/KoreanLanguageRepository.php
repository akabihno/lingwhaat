<?php

namespace App\Repository;

use App\Entity\KoreanLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class KoreanLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KoreanLanguageEntity::class);
    }

}
