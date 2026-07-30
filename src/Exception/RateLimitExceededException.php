<?php

namespace App\Exception;

class RateLimitExceededException extends \RuntimeException
{
    public function __construct(private readonly int $retryAfter)
    {
        parent::__construct('Слишком много запросов. Повторите попытку позже');
    }

    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }
}
