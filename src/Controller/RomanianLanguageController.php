<?php

namespace App\Controller;

use App\Entity\RomanianLanguageEntity;
use App\Repository\RomanianLanguageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;

class RomanianLanguageController extends LanguageController
{
    #[Route('/romanian_word', name: 'get_romanian_word', methods: ['GET'])]
    public function getWord(EntityManagerInterface $entityManager): ?Response
    {
        /* @var RomanianLanguageRepository  $repository */
        $repository = $entityManager->getRepository(RomanianLanguageEntity::class);
        $result = $repository->findByName($_GET['get_romanian_word']);

        if ($result) {
            /* @var RomanianLanguageEntity  $language*/
            foreach ($result as $language) {
                return new Response('id: ' . $language->getId() . ', name: ' . $language->getName() . 'ipa: ' . $language->getIpa());
            }
        }

        return null;

    }

}