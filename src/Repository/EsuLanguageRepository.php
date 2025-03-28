<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

class EsuLanguageRepository extends EntityRepository
{
    public function findAllOrderedByName()
    {
        return $this->getEntityManager()
            ->createQuery(
                'SELECT p FROM lingwhaat.pronunciation_esu_language p ORDER BY p.name ASC'
            )
            ->getResult();
    }

}