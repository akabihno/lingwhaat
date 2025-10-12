<?php

namespace App\Controller;

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

require dirname(__DIR__, 2).'/vendor/autoload.php';

class IndexController extends AbstractController
{
    public function __construct()
    {
        $dotenv = new Dotenv();
        $dotenv->loadEnv(dirname(__DIR__, 2).'/.env');
    }
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): RedirectResponse
    {
        return $this->redirectToRoute('api_doc');
    }

}