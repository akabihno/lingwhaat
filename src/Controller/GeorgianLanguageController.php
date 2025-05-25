<?php

namespace App\Controller;

use App\Entity\GeorgianLanguageEntity;
use App\Repository\GeorgianLanguageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;

class GeorgianLanguageController extends LanguageController
{
    #[Route('/georgian_word', name: 'get_georgian_word', methods: ['GET'])]
    public function getWord(EntityManagerInterface $entityManager): Response
    {
        /* @var GeorgianLanguageRepository $repository*/
        $repository = $entityManager->getRepository(GeorgianLanguageEntity::class);
        $result = $repository->findByName($_GET['get_georgian_word']);

        if ($result) {
            /* @var GeorgianLanguageEntity $language*/
            foreach ($result as $language) {
                return $this->returnResponse($language);
            }
        }

        return $this->returnNotFound();

    }

}