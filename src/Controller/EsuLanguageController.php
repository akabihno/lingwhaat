<?php

namespace App\Controller;

use App\Entity\EsuLanguageEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

class EsuLanguageController extends AbstractController
{
    #[Route('/language', name: 'get_language', methods: ['GET', 'POST'])]
    public function getLanguageData(EntityManagerInterface $entityManager): Response
    {
        $language = new EsuLanguageEntity();
        $language->setId(1);
        $language->setName('tengssuun');
        $language->setIpa('{{IPA|esu|/ˌtɨŋˈsu.un/|[ˌtɨŋˈsuːn];{{hyph|esu|tengs|suun;');

        //$entityManager->persist($language);

        //$entityManager->flush();

        $esuLanguageRepository = $entityManager->getRepository(EsuLanguageEntity::class);
        $result = $esuLanguageRepository->findAllOrderedByName();

        echo $result;

        return new Response('Saved new language record with id '.$language->getId());
    }

}