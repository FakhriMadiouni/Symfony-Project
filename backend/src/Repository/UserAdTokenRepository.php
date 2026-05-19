<?php namespace App\Repository;
use App\Entity\UserAdToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
class UserAdTokenRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $r) { parent::__construct($r, UserAdToken::class); }

    /** Tokens owned by user that are still active and not yet attached to an ad. */
    public function findAvailableForUser(int $userId): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.user = :uid')
            ->setParameter('uid', $userId)
            ->andWhere('t.active = 1')
            ->orderBy('t.creationDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByUser(int $userId, int $limit = 50, int $offset = 0): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.user = :uid')
            ->setParameter('uid', $userId)
            ->orderBy('t.creationDate', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }
}
