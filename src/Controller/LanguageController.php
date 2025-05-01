<?php

namespace App\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Response;

require dirname(__DIR__, 2).'/vendor/autoload.php';

class LanguageController extends AbstractController
{
    public function __construct()
    {
        $dotenv = new Dotenv();
        $dotenv->loadEnv(dirname(__DIR__, 2).'/.env');
    }

    protected function returnResponse($language): Response
    {
        return new Response('id: ' . $language->getId() . ', name: ' . $language->getName() . ', ipa: ' . $language->getIpa());
    }

    protected function returnNotFound(): Response
    {
        return new Response('No matching word found.', Response::HTTP_NOT_FOUND);
    }


}