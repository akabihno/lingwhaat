<?php

namespace App\Controller;

use App\Entity\GermanLanguageEntity;
use App\Repository\GermanLanguageRepository;

class GermanLanguageController extends LanguageController
{
    #[Route('/german_word', name: 'get_german_word', methods: ['GET'])]
    public function getWord(EntityManagerInterface $entityManager): ?Response
    {
        /* @var GermanLanguageRepository  $germanLanguageRepository */
        $germanLanguageRepository = $entityManager->getRepository(GermanLanguageEntity::class);
        $result = $germanLanguageRepository->findByName($_GET['get_german_word']);

        if ($result) {
            /* @var GermanLanguageEntity  $language*/
            foreach ($result as $language) {
                return new Response('id: ' . $language->getId() . ', name: ' . $language->getName() . 'ipa: ' . $language->getIpa());
            }
        }

        return null;

    }

}