<?php

namespace App\Controller;

use App\Entity\RussianLanguageEntity;
use App\Repository\RussianLanguageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;

class RussianLanguageController extends LanguageController
{
    #[Route('/russian_word', name: 'get_russian_word', methods: ['GET'])]
    public function getWord(EntityManagerInterface $entityManager): Response
    {
        /* @var RussianLanguageRepository  $repository */
        $repository = $entityManager->getRepository(RussianLanguageEntity::class);
        $result = $repository->findByName($_GET['get_russian_word']);

        if ($result) {
            /* @var RussianLanguageEntity  $language*/
            foreach ($result as $language) {
                return $this->returnResponse($language);
            }
        }

        return $this->returnNotFound();

    }

}