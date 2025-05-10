<?php

namespace App\Repository;

use App\Entity\ItalianLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class ItalianLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ItalianLanguageEntity::class);
    }

}