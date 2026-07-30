<?php

namespace App\Repository;

use App\Entity\Contact;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ContactRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Contact::class);
    }

    public function metrics(): array
    {
        $today = new \DateTimeImmutable('today', new \DateTimeZone('UTC'));
        $summary = $this->createQueryBuilder('contact')
            ->select('COUNT(contact.id) AS total')
            ->addSelect('SUM(CASE WHEN contact.createdAt >= :today THEN 1 ELSE 0 END) AS today')
            ->addSelect("SUM(CASE WHEN contact.notificationStatus = 'sent' THEN 1 ELSE 0 END) AS notifications_sent")
            ->addSelect("SUM(CASE WHEN contact.aiProvider = 'fallback' THEN 1 ELSE 0 END) AS ai_fallbacks")
            ->setParameter('today', $today)
            ->getQuery()
            ->getSingleResult();

        $categoryRows = $this->createQueryBuilder('contact')
            ->select('contact.category AS category, COUNT(contact.id) AS total')
            ->groupBy('contact.category')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getArrayResult();

        $categories = [];

        foreach ($categoryRows as $row) {
            $categories[$row['category']] = (int) $row['total'];
        }

        return [
            'total' => (int) $summary['total'],
            'today' => (int) ($summary['today'] ?? 0),
            'notifications_sent' => (int) ($summary['notifications_sent'] ?? 0),
            'ai_fallbacks' => (int) ($summary['ai_fallbacks'] ?? 0),
            'categories' => $categories,
        ];
    }
}
