<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

class FrenchLanguageRepository extends EntityRepository
{
    public function findByName($name)
    {
        $qb = $this->createQueryBuilder('e')
            ->where('e.name = :name')
            ->setParameter('name', $name);

        $query = $qb->getQuery();

        return $query->execute();
    }

}