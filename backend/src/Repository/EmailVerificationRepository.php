<?php

namespace App\Repository;

use App\Entity\EmailVerification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EmailVerificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailVerification::class);
    }

    public function findPendingCode(int $userId, string $code, string $type): ?EmailVerification
    {
        return $this->createQueryBuilder('ev')
            ->where('ev.user = :uid')
            ->andWhere('ev.code = :code')
            ->andWhere('ev.type = :type')
            ->andWhere('ev.verified = 0')
            ->setParameter('uid', $userId)
            ->setParameter('code', $code)
            ->setParameter('type', $type)
            ->orderBy('ev.expiryDate', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function invalidatePending(int $userId, string $type): void
    {
        $this->createQueryBuilder('ev')
            ->update()
            ->set('ev.verified', 2)
            ->where('ev.user = :uid')
            ->andWhere('ev.type = :type')
            ->andWhere('ev.verified = 0')
            ->setParameter('uid', $userId)
            ->setParameter('type', $type)
            ->getQuery()
            ->execute();
    }

    public function deleteExpiredUnverified(): int
    {
        return $this->createQueryBuilder('ev')
            ->delete()
            ->where('ev.verified = 0')
            ->andWhere('ev.expiryDate <= :now')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->execute();
    }
}
