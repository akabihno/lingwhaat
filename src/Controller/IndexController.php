<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class IndexController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): RedirectResponse
    {
        return $this->redirectToRoute('nelmio_api_doc');
    }

}