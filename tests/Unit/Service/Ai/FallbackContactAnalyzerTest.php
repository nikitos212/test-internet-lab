<?php

namespace App\Tests\Unit\Service\Ai;

use App\Dto\ContactInput;
use App\Service\Ai\FallbackContactAnalyzer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FallbackContactAnalyzerTest extends TestCase
{
    #[DataProvider('categoryCases')]
    public function testClassifiesContact(string $comment, string $expected): void
    {
        $input = new ContactInput('Анна', '+79991234567', 'anna@example.com', $comment);
        $analysis = (new FallbackContactAnalyzer())->analyze($input);

        self::assertSame($expected, $analysis->category);
        self::assertSame('fallback', $analysis->provider);
        self::assertNotEmpty($analysis->reply);
    }

    public static function categoryCases(): iterable
    {
        yield 'project' => ['Нужно разработать API для сервиса', 'project'];
        yield 'job' => ['Предлагаем работу на позиции PHP developer', 'job'];
        yield 'partnership' => ['Интересует совместное сотрудничество', 'partnership'];
        yield 'other' => ['Хочу задать несколько вопросов по вашему опыту', 'other'];
    }
}
