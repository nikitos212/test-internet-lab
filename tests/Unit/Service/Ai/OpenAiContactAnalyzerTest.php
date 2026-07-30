<?php

namespace App\Tests\Unit\Service\Ai;

use App\Dto\ContactInput;
use App\Service\Ai\OpenAiContactAnalyzer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class OpenAiContactAnalyzerTest extends TestCase
{
    public function testParsesStructuredResponse(): void
    {
        $body = json_encode([
            'output' => [
                [
                    'type' => 'message',
                    'content' => [
                        [
                            'type' => 'output_text',
                            'text' => json_encode([
                                'category' => 'project',
                                'sentiment' => 'positive',
                                'reply' => 'Спасибо за обращение. Я изучу задачу и скоро отвечу.',
                            ], JSON_UNESCAPED_UNICODE),
                        ],
                    ],
                ],
            ],
        ], JSON_UNESCAPED_UNICODE);
        $client = new MockHttpClient(new MockResponse($body));
        $analyzer = new OpenAiContactAnalyzer($client, 'test-key', 'test-model', 'test-secret');
        $input = new ContactInput(
            'Анна',
            '+79991234567',
            'anna@example.com',
            'Нужна разработка API для нового сервиса',
        );

        $analysis = $analyzer->analyze($input);

        self::assertSame('project', $analysis->category);
        self::assertSame('positive', $analysis->sentiment);
        self::assertSame('openai', $analysis->provider);
    }

    public function testRequiresApiKey(): void
    {
        $analyzer = new OpenAiContactAnalyzer(new MockHttpClient(), '', 'test-model', 'test-secret');
        $input = new ContactInput('Анна', '+79991234567', 'anna@example.com', 'Комментарий для теста');

        $this->expectException(\RuntimeException::class);
        $analyzer->analyze($input);
    }
}
