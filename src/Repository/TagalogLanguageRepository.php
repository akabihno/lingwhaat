<?php

namespace App\Repository;

use App\Entity\TagalogLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class TagalogLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TagalogLanguageEntity::class);
    }

}