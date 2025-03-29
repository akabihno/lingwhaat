<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

class EsuLanguageRepository extends ServiceEntityRepository
{
    public function findAllOrderedByName()
    {
        $qb = $this->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', 1);

        $query = $qb->getQuery();

        return $query->execute();
    }

}