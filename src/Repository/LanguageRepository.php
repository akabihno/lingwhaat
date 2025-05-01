<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

class LanguageRepository extends EntityRepository
{
    public function checkLanguage($word): bool
    {
        $result = $this->findByName($word);

        if ($result) {
            return true;
        }

        return false;
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