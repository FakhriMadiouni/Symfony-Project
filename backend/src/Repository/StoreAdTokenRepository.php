<?php namespace App\Repository;
use App\Entity\StoreAdToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
class StoreAdTokenRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $r) { parent::__construct($r, StoreAdToken::class); }
    public function findAllCurrentlyActive(): array {
        $now = new \DateTime();
        return $this->createQueryBuilder('t')
            ->where('t.active = 1')
            ->andWhere('t.offerStartDate IS NULL OR t.offerStartDate <= :now')
            ->andWhere('t.offerExpirationDate IS NULL OR t.offerExpirationDate > :now')
            ->setParameter('now', $now)
            ->orderBy('t.name','ASC')
            ->getQuery()->getResult();
    }
}
