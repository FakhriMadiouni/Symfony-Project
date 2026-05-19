<?php

namespace App\Repository;

use App\Entity\UserSession;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserSession::class);
    }

    public function findValidByToken(string $token): ?UserSession
    {
        return $this->createQueryBuilder('s')
            ->join('s.user', 'u')
            ->where('s.token = :token')
            ->andWhere('s.expiryDate > :now')
            ->setParameter('token', $token)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function deleteAllForUser(int $userId): void
    {
        $this->createQueryBuilder('s')
            ->delete()
            ->where('s.user = :uid')
            ->setParameter('uid', $userId)
            ->getQuery()
            ->execute();
    }

    public function deleteExpired(): int
    {
        return $this->createQueryBuilder('s')
            ->delete()
            ->where('s.expiryDate <= :now')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->execute();
    }
}
