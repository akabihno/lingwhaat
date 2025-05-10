<?php

namespace App\Controller;

use App\Entity\EstonianLanguageEntity;
use App\Repository\EstonianLanguageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;

class EstonianLanguageController extends LanguageController
{
    #[Route('/estonian_word', name: 'get_estonian_word', methods: ['GET'])]
    public function getWord(EntityManagerInterface $entityManager): Response
    {
        /* @var EstonianLanguageRepository $repository*/
        $repository = $entityManager->getRepository(EstonianLanguageEntity::class);
        $result = $repository->findByName($_GET['get_estonian_word']);

        if ($result) {
            /* @var EstonianLanguageEntity $language*/
            foreach ($result as $language) {
                return $this->returnResponse($language);
            }
        }

        return $this->returnNotFound();

    }

}