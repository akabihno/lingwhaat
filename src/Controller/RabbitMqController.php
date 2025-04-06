<?php

namespace App\Controller;

use Enqueue\Client\ProducerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RabbitMqController extends AbstractController
{
    #[Route('/send-to-rabbitmq', name: 'send_to_rabbitmq')]
    public function sendToRabbitMQ(ProducerInterface $producer): Response
    {
        $message = [
            'id' => uniqid(),
            'action' => 'process_redis_task',
            'payload' => ['foo' => 'bar']
        ];

        $producer->sendEvent('redis_messages', $message);

        return new Response('Message sent to RabbitMQ');
    }

}