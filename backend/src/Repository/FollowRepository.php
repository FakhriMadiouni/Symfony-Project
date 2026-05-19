<?php namespace App\Repository;
use App\Entity\Follow;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
class FollowRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $r) { parent::__construct($r, Follow::class); }
    public function isFollowing(int $followerId, int $followedId): bool {
        return (bool)$this->findOneBy(['follower' => $followerId, 'followedUser' => $followedId]);
    }
    public function countFollowers(int $userId): int {
        return (int)$this->createQueryBuilder('f')->select('COUNT(f.follower)')
            ->where('f.followedUser = :u')->setParameter('u',$userId)->getQuery()->getSingleScalarResult();
    }
    public function countFollowing(int $userId): int {
        return (int)$this->createQueryBuilder('f')->select('COUNT(f.followedUser)')
            ->where('f.follower = :u')->setParameter('u',$userId)->getQuery()->getSingleScalarResult();
    }
}
