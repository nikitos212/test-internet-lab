<?php

namespace App\Entity;

use App\Dto\ContactAnalysis;
use App\Dto\ContactInput;
use App\Repository\ContactRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContactRepository::class)]
#[ORM\Index(columns: ['created_at'], name: 'idx_contact_created_at')]
#[ORM\Index(columns: ['category'], name: 'idx_contact_category')]
class Contact
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 80)]
    private string $name;

    #[ORM\Column(length: 32)]
    private string $phone;

    #[ORM\Column(length: 180)]
    private string $email;

    #[ORM\Column(type: 'text')]
    private string $comment;

    #[ORM\Column(length: 24)]
    private string $category;

    #[ORM\Column(length: 16)]
    private string $sentiment;

    #[ORM\Column(type: 'text')]
    private string $generatedReply;

    #[ORM\Column(length: 32)]
    private string $aiProvider;

    #[ORM\Column(length: 16, options: ['default' => 'pending'])]
    private string $notificationStatus = 'pending';

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(ContactInput $input, ContactAnalysis $analysis)
    {
        $this->name = $input->name;
        $this->phone = $input->phone;
        $this->email = mb_strtolower($input->email);
        $this->comment = $input->comment;
        $this->category = $analysis->category;
        $this->sentiment = $analysis->sentiment;
        $this->generatedReply = $analysis->reply;
        $this->aiProvider = $analysis->provider;
        $this->createdAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getSentiment(): string
    {
        return $this->sentiment;
    }

    public function getGeneratedReply(): string
    {
        return $this->generatedReply;
    }

    public function getAiProvider(): string
    {
        return $this->aiProvider;
    }

    public function getNotificationStatus(): string
    {
        return $this->notificationStatus;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function markNotificationStatus(string $status): void
    {
        $this->notificationStatus = $status;
    }
}
