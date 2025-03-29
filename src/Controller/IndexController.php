<?php

namespace App\Controller;

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