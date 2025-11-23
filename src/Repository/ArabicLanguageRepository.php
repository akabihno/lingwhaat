<?php

namespace App\Repository;

use App\Entity\ArabicLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class ArabicLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ArabicLanguageEntity::class);
    }

}