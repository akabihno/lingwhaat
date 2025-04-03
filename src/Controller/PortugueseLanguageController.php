<?php

namespace App\Controller;

use App\Entity\PortugueseLanguageEntity;
use App\Repository\PortugueseLanguageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;

class PortugueseLanguageController extends LanguageController
{
    #[Route('/portuguese_word', name: 'get_portuguese_word', methods: ['GET'])]
    public function getWord(EntityManagerInterface $entityManager): Response
    {
        /* @var PortugueseLanguageRepository  $repository */
        $repository = $entityManager->getRepository(PortugueseLanguageEntity::class);
        $result = $repository->findByName($_GET['get_portuguese_word']);

        if ($result) {
            /* @var PortugueseLanguageEntity  $language*/
            foreach ($result as $language) {
                return $this->returnResponse($language);
            }
        }

        return $this->returnNotFound();

    }

}