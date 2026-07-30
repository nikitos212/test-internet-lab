<?php

namespace App\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Uid\Uuid;

class ApiRequestLogSubscriber implements EventSubscriberInterface
{
    public function __construct(
        #[Autowire(service: 'monolog.logger.api')]
        private readonly LoggerInterface $apiLogger,
        #[Autowire('%env(APP_SECRET)%')]
        private readonly string $appSecret,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 100],
            KernelEvents::RESPONSE => ['onResponse', -100],
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $incomingId = (string) $request->headers->get('X-Request-ID', '');
        $requestId = preg_match('/^[A-Za-z0-9._-]{8,64}$/', $incomingId) === 1
            ? $incomingId
            : Uuid::v7()->toRfc4122();

        $request->attributes->set('_request_id', $requestId);
        $request->attributes->set('_request_started_at', microtime(true));

        $this->apiLogger->info('api.request.received', [
            'request_id' => $requestId,
            'method' => $request->getMethod(),
            'path' => $request->getPathInfo(),
            'client' => hash_hmac('sha256', $request->getClientIp() ?? 'unknown', $this->appSecret),
        ]);
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $requestId = $request->attributes->get('_request_id');

        if (!is_string($requestId)) {
            return;
        }

        $startedAt = (float) $request->attributes->get('_request_started_at', microtime(true));
        $duration = (int) round((microtime(true) - $startedAt) * 1000);
        $response = $event->getResponse();
        $response->headers->set('X-Request-ID', $requestId);

        $this->apiLogger->info('api.request.completed', [
            'request_id' => $requestId,
            'method' => $request->getMethod(),
            'path' => $request->getPathInfo(),
            'status' => $response->getStatusCode(),
            'duration_ms' => $duration,
        ]);
    }
}
