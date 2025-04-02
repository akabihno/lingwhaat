<?php

namespace App\Controller;

use App\Entity\GreekLanguageEntity;
use App\Repository\GreekLanguageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;

class GreekLanguageController extends LanguageController
{
    #[Route('/greek_word', name: 'get_greek_word', methods: ['GET'])]
    public function getWord(EntityManagerInterface $entityManager): ?Response
    {
        /* @var GreekLanguageRepository  $repository */
        $repository = $entityManager->getRepository(GreekLanguageEntity::class);
        $result = $repository->findByName($_GET['get_greek_word']);

        if ($result) {
            /* @var GreekLanguageEntity  $language*/
            foreach ($result as $language) {
                return new Response('id: ' . $language->getId() . ', name: ' . $language->getName() . 'ipa: ' . $language->getIpa());
            }
        }

        return null;

    }

}