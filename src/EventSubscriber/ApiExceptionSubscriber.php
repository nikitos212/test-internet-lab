<?php

namespace App\EventSubscriber;

use App\Exception\ApiValidationException;
use App\Exception\RateLimitExceededException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => 'onKernelException'];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();

        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $exception = $event->getThrowable();
        $status = JsonResponse::HTTP_INTERNAL_SERVER_ERROR;
        $title = 'Внутренняя ошибка сервиса';
        $detail = 'Не удалось обработать запрос';
        $headers = [];
        $errors = null;

        if ($exception instanceof ApiValidationException) {
            $status = JsonResponse::HTTP_UNPROCESSABLE_ENTITY;
            $title = 'Ошибка валидации';
            $detail = $exception->getMessage();
            $errors = $exception->getErrors();
        } elseif ($exception instanceof JsonException) {
            $status = JsonResponse::HTTP_BAD_REQUEST;
            $title = 'Некорректный запрос';
            $detail = 'Проверьте формат JSON';
        } elseif ($exception instanceof RateLimitExceededException) {
            $status = JsonResponse::HTTP_TOO_MANY_REQUESTS;
            $title = 'Лимит запросов исчерпан';
            $detail = $exception->getMessage();
            $headers['Retry-After'] = (string) $exception->getRetryAfter();
        } elseif ($exception instanceof HttpExceptionInterface) {
            $status = $exception->getStatusCode();
            $headers = $exception->getHeaders();
            [$title, $detail] = $this->httpErrorText($status);
        } else {
            $this->logger->error('Unhandled API exception', [
                'request_id' => $request->attributes->get('_request_id'),
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
        }

        $body = [
            'type' => 'about:blank',
            'title' => $title,
            'status' => $status,
            'detail' => $detail,
            'request_id' => $request->attributes->get('_request_id'),
        ];

        if ($errors !== null) {
            $body['errors'] = $errors;
        }

        $response = new JsonResponse($body, $status, $headers);
        $response->headers->set('Content-Type', 'application/problem+json');
        $event->setResponse($response);
    }

    private function httpErrorText(int $status): array
    {
        return match ($status) {
            400 => ['Некорректный запрос', 'Проверьте формат JSON'],
            404 => ['Ресурс не найден', 'Запрошенный адрес не существует'],
            405 => ['Метод не поддерживается', 'Используйте разрешенный HTTP-метод'],
            415 => ['Неверный тип содержимого', 'Требуется Content-Type application/json'],
            default => ['Ошибка запроса', 'Не удалось обработать запрос'],
        };
    }
}
