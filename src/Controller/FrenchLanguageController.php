<?php

namespace App\Controller;

use App\Entity\FrenchLanguageEntity;
use App\Repository\FrenchLanguageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;

class FrenchLanguageController extends LanguageController
{
    #[Route('/french_word', name: 'get_french_word', methods: ['GET'])]
    public function getWord(EntityManagerInterface $entityManager): ?Response
    {
        /* @var FrenchLanguageRepository  $repository */
        $repository = $entityManager->getRepository(FrenchLanguageEntity::class);
        $result = $repository->findByName($_GET['get_french_word']);

        if ($result) {
            /* @var FrenchLanguageEntity  $language*/
            foreach ($result as $language) {
                return new Response('id: ' . $language->getId() . ', name: ' . $language->getName() . 'ipa: ' . $language->getIpa());
            }
        }

        return null;

    }

}