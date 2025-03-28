<?php

namespace App\Controller;

use App\Entity\EsuLanguageEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

class EsuLanguageController extends AbstractController
{
    public function getLanguageData(): Response
    {
        $language = new EsuLanguageEntity();
        $language->setId(1);
        $language->setName('tengssuun');
        $language->setIpa('{{IPA|esu|/ˌtɨŋˈsu.un/|[ˌtɨŋˈsuːn];{{hyph|esu|tengs|suun;');

        //$entityManager->persist($language);

        //$entityManager->flush();

        return new Response('Saved new language record with id '.$language->getId());
    }

}