<?php

namespace App\Repository;

use App\Entity\Advertisement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AdvertisementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Advertisement::class);
    }

    /** @return Advertisement[] */
    public function findWithPositiveTimeLeft(): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.active = 1')
            ->andWhere('a.timeLeft > 0')
            ->getQuery()
            ->getResult();
    }

    /**
     * Public listing — active, not banned, not hidden, with optional filters.
     */
    public function findPublic(
        ?int    $subcategoryId = null,
        ?int    $countryId = null,
        ?string $search = null,
        string  $sort = 'newest',
        int     $limit = 20,
        int     $offset = 0
    ): array {
        $qb = $this->createQueryBuilder('a')
            ->join('a.user', 'u')
            ->join('a.subcategory', 'sc')
            ->join('sc.category', 'cat')
            ->join('a.country', 'c')
            ->where('a.active = 1')
            ->andWhere('a.banStatus = 0')
            ->andWhere('a.hiddenByAdvertiser = 0')
            ->andWhere('a.lockStatus = 0')
            ->andWhere('u.banStatus = 0');

        if ($subcategoryId) {
            $qb->andWhere('a.subcategory = :sc')->setParameter('sc', $subcategoryId);
        }
        if ($countryId) {
            $qb->andWhere('a.country = :c')->setParameter('c', $countryId);
        }
        if ($search) {
            $qb->andWhere('a.title LIKE :s OR a.description LIKE :s')
               ->setParameter('s', '%' . $search . '%');
        }

        $orderMap = [
            'newest'    => 'a.creationDate DESC',
            'oldest'    => 'a.creationDate ASC',
            'price_asc' => 'a.price ASC',
            'price_desc'=> 'a.price DESC',
        ];
        $qb->orderBy(...explode(' ', $orderMap[$sort] ?? 'a.creationDate DESC'));

        return $qb->setMaxResults($limit)->setFirstResult($offset)->getQuery()->getResult();
    }

    public function countPublic(
        ?int    $subcategoryId = null,
        ?int    $countryId = null,
        ?string $search = null
    ): int {
        $qb = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->join('a.user', 'u')
            ->where('a.active = 1')
            ->andWhere('a.banStatus = 0')
            ->andWhere('a.hiddenByAdvertiser = 0')
            ->andWhere('a.lockStatus = 0')
            ->andWhere('u.banStatus = 0');

        if ($subcategoryId) {
            $qb->andWhere('a.subcategory = :sc')->setParameter('sc', $subcategoryId);
        }
        if ($countryId) {
            $qb->andWhere('a.country = :c')->setParameter('c', $countryId);
        }
        if ($search) {
            $qb->andWhere('a.title LIKE :s OR a.description LIKE :s')
               ->setParameter('s', '%' . $search . '%');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /** Ads belonging to a specific user (their store/listings) */
    public function findByUser(int $userId, bool $includeHidden = false, int $limit = 20, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.user = :uid')
            ->setParameter('uid', $userId);

        if (!$includeHidden) {
            $qb->andWhere('a.hiddenByAdvertiser = 0');
        }

        return $qb->orderBy('a.creationDate', 'DESC')
                  ->setMaxResults($limit)
                  ->setFirstResult($offset)
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Feed: ads from users that $viewerUserId follows.
     */
    public function findPublicFollowed(
        int     $viewerUserId,
        ?string $search = null,
        string  $sort = 'newest',
        int     $limit = 20,
        int     $offset = 0
    ): array {
        $qb = $this->createQueryBuilder('a')
            ->join('a.user', 'u')
            ->join('u.followers', 'f')
            ->where('f.follower = :me')
            ->setParameter('me', $viewerUserId)
            ->andWhere('a.active = 1')
            ->andWhere('a.banStatus = 0')
            ->andWhere('a.hiddenByAdvertiser = 0')
            ->andWhere('a.lockStatus = 0')
            ->andWhere('u.banStatus = 0');

        if ($search) {
            $qb->andWhere('a.title LIKE :s OR a.description LIKE :s')
               ->setParameter('s', '%' . $search . '%');
        }

        $orderMap = [
            'newest'     => 'a.creationDate DESC',
            'oldest'     => 'a.creationDate ASC',
            'price_asc'  => 'a.price ASC',
            'price_desc' => 'a.price DESC',
        ];
        $qb->orderBy(...explode(' ', $orderMap[$sort] ?? 'a.creationDate DESC'));

        return $qb->setMaxResults($limit)->setFirstResult($offset)->getQuery()->getResult();
    }

    public function countPublicFollowed(int $viewerUserId, ?string $search = null): int
    {
        $qb = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->join('a.user', 'u')
            ->join('u.followers', 'f')
            ->where('f.follower = :me')
            ->setParameter('me', $viewerUserId)
            ->andWhere('a.active = 1')
            ->andWhere('a.banStatus = 0')
            ->andWhere('a.hiddenByAdvertiser = 0')
            ->andWhere('a.lockStatus = 0')
            ->andWhere('u.banStatus = 0');

        if ($search) {
            $qb->andWhere('a.title LIKE :s OR a.description LIKE :s')
               ->setParameter('s', '%' . $search . '%');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
