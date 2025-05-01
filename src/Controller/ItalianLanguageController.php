<?php

namespace App\Controller;

use App\Entity\ItalianLanguageEntity;
use App\Repository\ItalianLanguageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;

class ItalianLanguageController extends LanguageController
{
    #[Route('/italian_word', name: 'get_italian_word', methods: ['GET'])]
    public function getWord(EntityManagerInterface $entityManager): Response
    {
        /* @var ItalianLanguageRepository  $repository */
        $repository = $entityManager->getRepository(ItalianLanguageEntity::class);
        $result = $repository->findByName($_GET['get_italian_word']);

        if ($result) {
            /* @var ItalianLanguageEntity  $language*/
            foreach ($result as $language) {
                return $this->returnResponse($language);
            }
        }

        return $this->returnNotFound();

    }

}