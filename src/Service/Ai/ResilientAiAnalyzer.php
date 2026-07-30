<?php

namespace App\Service\Ai;

use App\Dto\ContactAnalysis;
use App\Dto\ContactInput;
use Psr\Log\LoggerInterface;

class ResilientAiAnalyzer implements AiAnalyzerInterface
{
    public function __construct(
        private readonly AiAnalyzerInterface $primary,
        private readonly AiAnalyzerInterface $fallback,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function analyze(ContactInput $input): ContactAnalysis
    {
        try {
            return $this->primary->analyze($input);
        } catch (\Throwable $exception) {
            $this->logger->warning('AI analysis fallback activated', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return $this->fallback->analyze($input);
        }
    }
}
