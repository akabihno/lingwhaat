<?php

namespace App\Controller;

use App\Entity\DutchLanguageEntity;
use App\Repository\DutchLanguageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;

class DutchLanguageController extends LanguageController
{
    #[Route('/dutch_word', name: 'get_dutch_word', methods: ['GET'])]
    public function getWord(EntityManagerInterface $entityManager): Response
    {
        /* @var DutchLanguageRepository $repository*/
        $repository = $entityManager->getRepository(DutchLanguageEntity::class);
        $result = $repository->findByName($_GET['get_dutch_word']);

        if ($result) {
            /* @var DutchLanguageEntity $language*/
            foreach ($result as $language) {
                return $this->returnResponse($language);
            }
        }

        return $this->returnNotFound();

    }

}