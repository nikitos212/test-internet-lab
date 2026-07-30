<?php

namespace App\Service;

use App\Dto\ContactInput;
use App\Dto\ContactResult;
use App\Entity\Contact;
use App\Service\Ai\AiAnalyzerInterface;
use Doctrine\ORM\EntityManagerInterface;

class ContactHandler
{
    public function __construct(
        private readonly AiAnalyzerInterface $analyzer,
        private readonly ContactNotificationSender $notificationSender,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function handle(ContactInput $input): ContactResult
    {
        $analysis = $this->analyzer->analyze($input);
        $contact = new Contact($input, $analysis);

        $this->entityManager->persist($contact);
        $this->entityManager->flush();

        $notification = $this->notificationSender->send($contact);
        $contact->markNotificationStatus($notification->status());
        $this->entityManager->flush();

        return new ContactResult($contact, $analysis, $notification);
    }
}
