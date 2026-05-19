<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /** @return User[] */
    public function findWithActivePunishments(): array
    {
        return $this->createQueryBuilder('u')
            ->where(
                '(u.banStatus = 1 AND u.banTimeLeft > 0)
                 OR (u.adBanStatus = 1 AND u.adBanTimeLeft > 0)
                 OR (u.muteStatus = 1 AND u.muteTimeLeft > 0)
                 OR (u.staffBan = 1 AND u.staffBanTimeLeft > 0)'
            )
            ->getQuery()
            ->getResult();
    }

    public function searchUsers(string $query, int $limit = 20, int $offset = 0): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.username LIKE :q OR u.email LIKE :q')
            ->setParameter('q', '%' . $query . '%')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }
}
