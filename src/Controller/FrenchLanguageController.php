<?php

namespace App\Controller;

use App\Entity\FrenchLanguageEntity;
use App\Repository\FrenchLanguageRepository;

class FrenchLanguageController extends LanguageController
{
    #[Route('/french_word', name: 'get_french_word', methods: ['GET'])]
    public function getWord(EntityManagerInterface $entityManager): ?Response
    {
        /* @var FrenchLanguageRepository  $frenchLanguageRepository */
        $frenchLanguageRepository = $entityManager->getRepository(FrenchLanguageEntity::class);
        $result = $frenchLanguageRepository->findByName($_GET['get_french_word']);

        if ($result) {
            /* @var FrenchLanguageEntity  $language*/
            foreach ($result as $language) {
                return new Response('id: ' . $language->getId() . ', name: ' . $language->getName() . 'ipa: ' . $language->getIpa());
            }
        }

        return null;

    }

}