<?php

namespace App\Dto;

use App\Entity\Contact;

final readonly class ContactResult
{
    public function __construct(
        public Contact $contact,
        public ContactAnalysis $analysis,
        public NotificationResult $notification,
    ) {
    }
}
