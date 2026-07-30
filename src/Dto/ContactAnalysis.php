<?php

namespace App\Dto;

final readonly class ContactAnalysis
{
    public function __construct(
        public string $category,
        public string $sentiment,
        public string $reply,
        public string $provider,
    ) {
    }
}
