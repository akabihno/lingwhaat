<?php

namespace App\Controller;

use App\Service\Metrics\PrometheusMetricsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MetricsController extends AbstractController
{
    #[Route('/metrics', name: 'metrics', methods: ['GET'])]
    public function metrics(PrometheusMetricsService $metrics): Response
    {
        return new Response(
            $metrics->render(),
            Response::HTTP_OK,
            ['Content-Type' => $metrics->contentType()],
        );
    }
}
