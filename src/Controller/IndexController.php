<?php

namespace App\Controller;

use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class IndexController extends AbstractController
{
    #[Route('/')]
    public function index(): Response
    {
        $number = random_int(0, 100);

        return $this->render('index.html.twig', [
            'test' => $number,
        ]);
    }

}