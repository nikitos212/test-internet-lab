<?php

namespace App\Controller;

use App\Dto\ContactInput;
use App\Exception\ApiValidationException;
use App\Exception\RateLimitExceededException;
use App\Service\ContactHandler;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ContactController extends AbstractController
{
    public function __construct(
        private readonly ContactHandler $handler,
        private readonly ValidatorInterface $validator,
        #[Autowire(service: 'limiter.contact')]
        private readonly RateLimiterFactory $contactLimiter,
    ) {
    }

    #[Route('/api/contact', name: 'api_contact_create', methods: ['POST'])]
    #[OA\Post(
        summary: 'Создать обращение',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'phone', 'email', 'comment'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Анна'),
                    new OA\Property(property: 'phone', type: 'string', example: '+7 999 123-45-67'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'anna@example.com'),
                    new OA\Property(property: 'comment', type: 'string', example: 'Нужна разработка API для нового сервиса'),
                ],
                type: 'object',
            ),
        ),
        tags: ['Contacts'],
        responses: [
            new OA\Response(response: 201, description: 'Обращение принято'),
            new OA\Response(response: 400, description: 'Некорректный JSON'),
            new OA\Response(response: 415, description: 'Неверный тип содержимого'),
            new OA\Response(response: 422, description: 'Ошибка валидации'),
            new OA\Response(response: 429, description: 'Превышен лимит запросов'),
        ],
    )]
    public function __invoke(Request $request): JsonResponse
    {
        if ($request->getContentTypeFormat() !== 'json') {
            throw new UnsupportedMediaTypeHttpException('Требуется Content-Type application/json');
        }

        $limit = $this->contactLimiter
            ->create(hash('sha256', $request->getClientIp() ?? 'unknown'))
            ->consume();

        if (!$limit->isAccepted()) {
            $retryAfter = max(1, $limit->getRetryAfter()->getTimestamp() - time());
            throw new RateLimitExceededException($retryAfter);
        }

        $input = ContactInput::fromArray($request->toArray());
        $violations = $this->validator->validate($input);

        if (count($violations) > 0) {
            $errors = [];

            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()][] = $violation->getMessage();
            }

            throw new ApiValidationException($errors);
        }

        $result = $this->handler->handle($input);
        $response = new JsonResponse([
            'data' => [
                'id' => $result->contact->getId(),
                'status' => 'created',
                'analysis' => [
                    'category' => $result->analysis->category,
                    'sentiment' => $result->analysis->sentiment,
                    'reply' => $result->analysis->reply,
                    'provider' => $result->analysis->provider,
                ],
                'notifications' => [
                    'status' => $result->notification->status(),
                    'owner_sent' => $result->notification->ownerSent,
                    'user_sent' => $result->notification->userSent,
                ],
                'created_at' => $result->contact->getCreatedAt()->format(DATE_ATOM),
            ],
        ], JsonResponse::HTTP_CREATED);

        $response->headers->set('X-RateLimit-Remaining', (string) $limit->getRemainingTokens());

        return $response;
    }
}
