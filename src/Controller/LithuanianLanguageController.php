<?php

namespace App\Controller;

use App\Entity\LithuanianLanguageEntity;
use App\Repository\LithuanianLanguageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;

class LithuanianLanguageController extends LanguageController
{
    #[Route('/lithuanian_word', name: 'get_lithuanian_word', methods: ['GET'])]
    public function getWord(EntityManagerInterface $entityManager): Response
    {
        /* @var LithuanianLanguageRepository  $repository */
        $repository = $entityManager->getRepository(LithuanianLanguageEntity::class);
        $result = $repository->findByName($_GET['get_lithuanian_word']);

        if ($result) {
            /* @var LithuanianLanguageEntity  $language*/
            foreach ($result as $language) {
                return $this->returnResponse($language);
            }
        }

        return $this->returnNotFound();

    }

}