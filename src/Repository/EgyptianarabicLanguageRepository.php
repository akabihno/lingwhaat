<?php

namespace App\Repository;

use App\Entity\EgyptianarabicLanguageEntity;
use Doctrine\Persistence\ManagerRegistry;

class EgyptianarabicLanguageRepository extends AbstractLanguageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EgyptianarabicLanguageEntity::class);
    }

}
