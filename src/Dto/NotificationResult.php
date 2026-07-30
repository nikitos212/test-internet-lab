<?php

namespace App\Dto;

final readonly class NotificationResult
{
    public function __construct(
        public bool $ownerSent,
        public bool $userSent,
    ) {
    }

    public function status(): string
    {
        if ($this->ownerSent && $this->userSent) {
            return 'sent';
        }

        if ($this->ownerSent || $this->userSent) {
            return 'partial';
        }

        return 'failed';
    }
}
