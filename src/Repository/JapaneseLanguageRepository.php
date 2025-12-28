<?php

namespace App\Repository;

use App\Entity\JapaneseLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class JapaneseLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JapaneseLanguageEntity::class);
    }

}
