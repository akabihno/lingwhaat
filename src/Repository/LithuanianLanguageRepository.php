<?php

namespace App\Repository;

use App\Entity\LithuanianLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class LithuanianLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LithuanianLanguageEntity::class);
    }

}