<?php

namespace App\Tests\Unit\Service\Ai;

use App\Dto\ContactAnalysis;
use App\Dto\ContactInput;
use App\Service\Ai\AiAnalyzerInterface;
use App\Service\Ai\ResilientAiAnalyzer;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class ResilientAiAnalyzerTest extends TestCase
{
    public function testUsesFallbackWhenPrimaryFails(): void
    {
        $primary = new class implements AiAnalyzerInterface {
            public function analyze(ContactInput $input): ContactAnalysis
            {
                throw new \RuntimeException('Provider unavailable');
            }
        };
        $fallback = new class implements AiAnalyzerInterface {
            public function analyze(ContactInput $input): ContactAnalysis
            {
                return new ContactAnalysis('other', 'neutral', 'Ответ без AI', 'fallback');
            }
        };
        $analyzer = new ResilientAiAnalyzer($primary, $fallback, new NullLogger());
        $input = new ContactInput('Анна', '+79991234567', 'anna@example.com', 'Комментарий для теста');

        $analysis = $analyzer->analyze($input);

        self::assertSame('fallback', $analysis->provider);
        self::assertSame('other', $analysis->category);
    }
}
