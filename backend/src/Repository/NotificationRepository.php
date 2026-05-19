<?php

namespace App\Repository;

use App\Entity\Notification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    public function countUnread(int $userId): int
    {
        return (int) $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.user = :uid AND n.readStatus = 0')
            ->setParameter('uid', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return Notification[] */
    public function findForUser(int $userId, int $limit = 100): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.user = :uid')
            ->setParameter('uid', $userId)
            ->orderBy('n.date', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function markAllRead(int $userId): void
    {
        $this->createQueryBuilder('n')
            ->update()
            ->set('n.readStatus', 1)
            ->where('n.user = :uid AND n.readStatus = 0')
            ->setParameter('uid', $userId)
            ->getQuery()
            ->execute();
    }

    public function findLastForUserAndRef(int $userId, string $category, int $refId): ?Notification
    {
        return $this->createQueryBuilder('n')
            ->where('n.user = :uid')
            ->andWhere('n.category = :cat')
            ->andWhere('n.referenceId = :rid')
            ->setParameter('uid', $userId)
            ->setParameter('cat', $category)
            ->setParameter('rid', $refId)
            ->orderBy('n.date', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
