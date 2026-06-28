<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Protects write endpoints (POST/PUT/PATCH/DELETE) under /api/word-category
 * with an API key supplied via the X-Api-Key request header.
 */
class ApiKeyAuthSubscriber implements EventSubscriberInterface
{
    private const string PROTECTED_PATH_PREFIX = '/api/word-category';

    public function __construct(
        private readonly string $apiKey
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $method = $request->getMethod();

        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return;
        }

        if (!str_starts_with($request->getPathInfo(), self::PROTECTED_PATH_PREFIX)) {
            return;
        }

        $providedKey = $request->headers->get('X-Api-Key', '');

        if (!hash_equals($this->apiKey, $providedKey)) {
            $event->setResponse(new JsonResponse(
                ['error' => 'Unauthorized: invalid or missing X-Api-Key header'],
                Response::HTTP_UNAUTHORIZED
            ));
        }
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 8],
        ];
    }
}
