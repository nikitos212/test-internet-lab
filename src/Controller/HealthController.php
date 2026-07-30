<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class HealthController
{
    public function __construct(
        private readonly Connection $connection,
        private readonly string $openAiApiKey,
    ) {
    }

    #[Route('/api/health', name: 'api_health', methods: ['GET'])]
    #[OA\Get(
        summary: 'Проверить состояние сервиса',
        tags: ['Service'],
        responses: [
            new OA\Response(response: 200, description: 'Сервис доступен'),
            new OA\Response(response: 503, description: 'База данных недоступна'),
        ],
    )]
    public function __invoke(): JsonResponse
    {
        try {
            $this->connection->executeQuery('SELECT 1')->fetchOne();
            $database = 'up';
            $statusCode = JsonResponse::HTTP_OK;
        } catch (\Throwable) {
            $database = 'down';
            $statusCode = JsonResponse::HTTP_SERVICE_UNAVAILABLE;
        }

        return new JsonResponse([
            'status' => $database === 'up' ? 'ok' : 'unavailable',
            'checks' => [
                'database' => $database,
                'ai' => $this->openAiApiKey !== '' ? 'configured' : 'fallback',
            ],
            'time' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(DATE_ATOM),
        ], $statusCode);
    }
}
