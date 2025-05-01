<?php

namespace App\Repository;

use App\Entity\GermanLanguageEntity;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class GermanLanguageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GermanLanguageEntity::class);
    }
    public function findByName($name)
    {
        $qb = $this->createQueryBuilder('e')
            ->where('e.name = :name')
            ->setParameter('name', $name);

        $query = $qb->getQuery();

        return $query->execute();
    }

}