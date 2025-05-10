<?php

namespace App\Controller;

use App\Entity\LatinLanguageEntity;
use App\Repository\LatinLanguageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;

class LatinLanguageController extends LanguageController
{
    #[Route('/latin_word', name: 'get_latin_word', methods: ['GET'])]
    public function getWord(EntityManagerInterface $entityManager): Response
    {
        /* @var LatinLanguageRepository $repository*/
        $repository = $entityManager->getRepository(LatinLanguageEntity::class);
        $result = $repository->findByName($_GET['get_latin_word']);

        if ($result) {
            /* @var LatinLanguageEntity $language*/
            foreach ($result as $language) {
                return $this->returnResponse($language);
            }
        }

        return $this->returnNotFound();

    }

}