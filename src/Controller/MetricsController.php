<?php

namespace App\Controller;

use App\Repository\ContactRepository;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class MetricsController
{
    public function __construct(private readonly ContactRepository $contacts)
    {
    }

    #[Route('/api/metrics', name: 'api_metrics', methods: ['GET'])]
    #[OA\Get(
        summary: 'Получить агрегированную статистику',
        tags: ['Service'],
        responses: [
            new OA\Response(response: 200, description: 'Статистика обращений'),
        ],
    )]
    public function __invoke(): JsonResponse
    {
        return new JsonResponse(['data' => $this->contacts->metrics()]);
    }
}
