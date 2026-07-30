<?php

namespace App\Service;

use App\Dto\NotificationResult;
use App\Entity\Contact;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class ContactNotificationSender
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
        private readonly string $ownerEmail,
        private readonly string $fromEmail,
    ) {
    }

    public function send(Contact $contact): NotificationResult
    {
        $ownerSent = $this->createAndSendSafely(
            fn (): TemplatedEmail => (new TemplatedEmail())
                ->from(new Address($this->fromEmail, 'Portfolio'))
                ->to($this->ownerEmail)
                ->replyTo(new Address($contact->getEmail(), $contact->getName()))
                ->subject('Новое обращение с сайта')
                ->htmlTemplate('email/owner.html.twig')
                ->textTemplate('email/owner.txt.twig')
                ->context(['contact' => $contact]),
            'owner',
            $contact,
        );

        $userSent = $this->createAndSendSafely(
            fn (): TemplatedEmail => (new TemplatedEmail())
                ->from(new Address($this->fromEmail, 'Portfolio'))
                ->to(new Address($contact->getEmail(), $contact->getName()))
                ->subject('Ваше обращение получено')
                ->htmlTemplate('email/user.html.twig')
                ->textTemplate('email/user.txt.twig')
                ->context(['contact' => $contact]),
            'user',
            $contact,
        );

        return new NotificationResult($ownerSent, $userSent);
    }

    private function createAndSendSafely(\Closure $emailFactory, string $recipient, Contact $contact): bool
    {
        try {
            $this->mailer->send($emailFactory());

            return true;
        } catch (\Throwable $exception) {
            $this->logger->error('Contact notification failed', [
                'contact_id' => $contact->getId(),
                'recipient' => $recipient,
                'exception' => $exception::class,
            ]);

            return false;
        }
    }
}
